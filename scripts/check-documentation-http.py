from urllib.request import Request, build_opener, HTTPRedirectHandler
from urllib.error import HTTPError
class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs): return None
opener = build_opener(NoRedirect)
def get(path, host):
    try: return opener.open(Request('http://127.0.0.1:8001'+path, headers={'Host':host}),timeout=15)
    except HTTPError as response: return response
for host in ['docs.vashfindir.ru','vashfindir.ru']:
    response=get('/robots.txt',host)
    assert response.status==200
    assert ('Sitemap: https://'+host+'/sitemap.xml') in response.read().decode()
    assert get('/',host).status==200
    assert get('/sitemap.xml',host).status==200
assert get('/search?q=test','docs.vashfindir.ru').status==200
assert get('/articles/not-present','docs.vashfindir.ru').status==404
response=get('/admin/docs/pages','docs.vashfindir.ru')
assert response.status==301 and response.headers['Location']=='https://vashfindir.ru/admin/docs/pages'
for path in ['/gazeta','/privacy','/offer']: assert get(path,'vashfindir.ru').status==200
print('OK: real local Nginx, both hosts, robots/sitemap, search, 404, legacy Admin redirect and website pages')
