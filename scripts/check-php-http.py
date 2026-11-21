"""Smoke production artifacts with synthetic credentials in the isolated stack."""

import base64
import http.cookiejar
import json
import re
import urllib.error
import urllib.parse
import urllib.request
from html.parser import HTMLParser


class Inputs(HTMLParser):
    def __init__(self, html, action=None):
        super().__init__()
        self.values = {}
        self.action = action
        self.form = None
        self.feed(html)

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'form':
            self.form = attrs.get('action')
        if tag == 'input' and 'name' in attrs and (self.action is None or self.action == self.form):
            self.values[attrs['name']] = attrs.get('value', '')

    def handle_endtag(self, tag):
        if tag == 'form':
            self.form = None


origin = 'http://127.0.0.1:18041'


class IsolatedRedirects(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        target = urllib.parse.urlsplit(newurl)
        assert (target.scheme, target.netloc) == ('http', '127.0.0.1:18041'), newurl
        return super().redirect_request(req, fp, code, msg, headers, newurl)


cookies = http.cookiejar.CookieJar()
browser = urllib.request.build_opener(urllib.request.ProxyHandler({}), IsolatedRedirects(), urllib.request.HTTPCookieProcessor(cookies))
public = urllib.request.build_opener(urllib.request.ProxyHandler({}), IsolatedRedirects())


def request(path, fields=None):
    data = None if fields is None else urllib.parse.urlencode(fields).encode()
    with browser.open(origin + path, data=data, timeout=20) as response:
        assert response.status == 200, (path, response.status)
        return response.url, response.read().decode()


def check_documentation_image():
    section_form = '/admin/docs/sections/new'
    section_url, _ = request(section_form, {
        'doc_section[_token]': Inputs(request(section_form)[1]).values['doc_section[_token]'],
        'doc_section[title]': 'Smoke',
        'doc_section[slug]': 'smoke',
        'doc_section[position]': '0',
    })
    match = re.fullmatch(r'/admin/docs/sections/(\d+)/edit', urllib.parse.urlsplit(section_url).path)
    assert match, section_url
    page_form = '/admin/docs/pages/new'
    page = {
        'doc_page[_token]': Inputs(request(page_form)[1]).values['doc_page[_token]'],
        'doc_page[title]': 'Screenshot smoke',
        'doc_page[slug]': 'screenshot-smoke',
        'doc_page[sectionId]': match[1],
        'doc_page[description]': 'Validates screenshot rendering in the production image.',
        'doc_page[body]': 'Follow the illustrated steps.',
        'doc_page[position]': '0',
    }
    edit_url, edit_html = request(page_form, page)
    edit_path = urllib.parse.urlsplit(edit_url).path
    match = re.fullmatch(r'(/admin/docs/pages/\d+)/edit', edit_path)
    assert match, edit_path
    article_admin = match[1]
    upload = article_admin + '/images'
    token = Inputs(edit_html, upload).values['_token']
    png = base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAIAAABLbSncAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAADElEQVQImWNgGB4AAADIAAH4XVuUAAAAAElFTkSuQmCC')
    boundary = 'vf-documentation-smoke'
    payload = (
        f'--{boundary}\r\nContent-Disposition: form-data; name="_token"\r\n\r\n{token}\r\n'
        f'--{boundary}\r\nContent-Disposition: form-data; name="image"; filename="screenshot.png"\r\n'
        'Content-Type: image/png\r\n\r\n'
    ).encode() + png + f'\r\n--{boundary}--\r\n'.encode()
    upload_request = urllib.request.Request(origin + upload, data=payload, headers={
        'Content-Type': 'multipart/form-data; boundary=' + boundary,
        'Accept': 'application/json',
    })
    with browser.open(upload_request, timeout=20) as response:
        assert response.status == 200, response.status
        screenshot = json.load(response)
        assert (screenshot['width'], screenshot['height'], screenshot['mimeType']) == (8, 8, 'image/webp')

    image_path = '/media/documentation/' + screenshot['id']

    def public_image(etag=None):
        headers = {'Host': 'docs.vashfindir.ru'}
        if etag:
            headers['If-None-Match'] = etag
        media_request = urllib.request.Request(origin + image_path, headers=headers)
        try:
            return public.open(media_request, timeout=20)
        except urllib.error.HTTPError as error:
            return error

    edit_fields = Inputs(edit_html).values
    page.update({
        'doc_page[_token]': edit_fields['doc_page[_token]'],
        'doc_page[version]': edit_fields['doc_page[version]'],
        'doc_page[body]': f"Follow the illustrated steps.\n\n![Example form]({screenshot['markdownDestination']})",
    })
    _, edit_html = request(edit_path, page)
    assert 'alt="Example form"' in request(article_admin + '/preview')[1]
    with public_image() as response:
        assert response.status == 404, response.status
    publish = article_admin + '/publish'
    _, published_html = request(publish, Inputs(edit_html, publish).values)
    docs_request = urllib.request.Request(origin + '/articles/screenshot-smoke', headers={'Host': 'docs.vashfindir.ru'})
    with public.open(docs_request, timeout=20) as response:
        html = response.read().decode()
        assert image_path in html and 'alt="Example form"' in html
    with public_image() as response:
        assert response.status == 200, response.status
        assert response.headers['Content-Type'] == 'image/webp'
        assert not response.headers.get_all('Set-Cookie')
        image = response.read()
        assert image[:4] == b'RIFF' and image[8:16] == b'WEBPVP8L', 'Expected lossless WebP'
        etag = response.headers['ETag']
        assert etag
    unpublish = article_admin + '/unpublish'
    request(unpublish, Inputs(published_html, unpublish).values)
    with public_image(etag) as response:
        assert response.status == 404, response.status


assert request('/health')[1].strip() == 'alive'
assert request('/')[0] == origin + '/'
assert not list(cookies), 'Public pages started a session'

# Host routing and the new module are exercised in APP_ENV=prod with compiled images.
for path, expected in [
    ('/', 'База знаний'),
    ('/search?q=test', 'Поиск по документации'),
    ('/robots.txt', 'Sitemap: https://docs.vashfindir.ru/sitemap.xml'),
    ('/sitemap.xml', 'https://docs.vashfindir.ru/'),
]:
    docs_request = urllib.request.Request(origin + path, headers={'Host': 'docs.vashfindir.ru'})
    with public.open(docs_request, timeout=20) as response:
        assert response.status == 200, (path, response.status)
        assert expected in response.read().decode(), path
        assert not response.headers.get_all('Set-Cookie'), 'Documentation started a session'

assert request('/admin')[0] == origin + '/admin/login'
_, login = request('/admin/login')
assert '_csrf_token' in Inputs(login).values
fields = {'_username': 'admin', '_password': 'admin', '_csrf_token': 'forged'}
assert request('/admin/login', fields)[0] == origin + '/admin/login'
assert request('/admin')[0] == origin + '/admin/login', 'Forged login CSRF accepted'
_, login = request('/admin/login')
fields['_csrf_token'] = Inputs(login).values['_csrf_token']
url, dashboard = request('/admin/login', fields)
assert url.rstrip('/') == origin + '/admin', 'Valid login rejected'
assert 'Панель контент-менеджера' in dashboard
assert 'Документация' in request('/admin/docs/pages')[1]
assert 'data-doc-body' in request('/admin/docs/pages/new')[1]
assert 'Новый раздел' in request('/admin/docs/sections/new')[1]
assert 'Выбор ссылки' in request('/admin/docs/link-picker')[1]
try:
    request('/admin/docs/pages/1/publish', {'_token': 'forged', 'version': 1})
except urllib.error.HTTPError as error:
    assert error.code == 403, error.code
else:
    raise AssertionError('Forged documentation CSRF accepted')
try:
    request('/admin/logout', {'_csrf_token': 'forged'})
except urllib.error.HTTPError as error:
    assert error.code == 403, error.code
else:
    raise AssertionError('Forged logout CSRF accepted')
check_documentation_image()
# Repeated requests exercise shared Postgres-backed sessions across FPM replicas.
for _ in range(6):
    url, dashboard = request('/admin')
    assert url.rstrip('/') == origin + '/admin'
logout = Inputs(dashboard).values['_csrf_token']
assert request('/admin/logout', {'_csrf_token': logout})[0] == origin + '/admin/login'
assert request('/admin')[0] == origin + '/admin/login', 'Logout left a session active'
print('Production HTTP smoke passed: website, documentation, screenshot upload and visibility, Admin login, shared session, CSRF, logout')
