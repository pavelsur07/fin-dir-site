"""Smoke production artifacts with synthetic credentials in the isolated stack."""

import http.cookiejar
import urllib.error
import urllib.parse
import urllib.request
from html.parser import HTMLParser


class Inputs(HTMLParser):
    def __init__(self, html):
        super().__init__()
        self.values = {}
        self.feed(html)

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'input' and 'name' in attrs:
            self.values[attrs['name']] = attrs.get('value', '')


origin = 'http://127.0.0.1:18041'
cookies = http.cookiejar.CookieJar()
browser = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookies))


def request(path, fields=None):
    data = None if fields is None else urllib.parse.urlencode(fields).encode()
    with browser.open(origin + path, data=data, timeout=20) as response:
        assert response.status == 200, (path, response.status)
        return response.url, response.read().decode()


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
    with urllib.request.urlopen(docs_request, timeout=20) as response:
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
# Repeated requests exercise shared Postgres-backed sessions across FPM replicas.
for _ in range(6):
    url, dashboard = request('/admin')
    assert url.rstrip('/') == origin + '/admin'
logout = Inputs(dashboard).values['_csrf_token']
assert request('/admin/logout', {'_csrf_token': logout})[0] == origin + '/admin/login'
assert request('/admin')[0] == origin + '/admin/login', 'Logout left a session active'
print('Production HTTP smoke passed: website, documentation, Admin UI, login, shared session, CSRF, logout')
