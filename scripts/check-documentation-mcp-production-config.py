"""Check both production runtimes with synthetic inputs; never load production secrets."""
from pathlib import Path
import json,os,subprocess

root=Path(__file__).resolve().parent.parent
env={**os.environ,'VF_SITE_APP_SECRET':'configuration-contract-only','VF_SITE_DB_PASSWORD':'configuration-contract-only','VF_ADMIN_PASSWORD_HASH':'configuration-contract-only'}
for key in ['VF_DOCS_MCP_HTTP_ENABLED','VF_DOCS_MCP_OAUTH_ISSUER']:
    env.pop(key,None)
for enabled,issuer in [('0',''),('1','https://issuer.test/realms/projects')]:
    case=env if enabled=='0' else {**env,'VF_DOCS_MCP_HTTP_ENABLED':enabled,'VF_DOCS_MCP_OAUTH_ISSUER':issuer}
    result=subprocess.run(['docker','compose','--env-file','/dev/null','--profile','cli','-f','docker-compose.prod.yml','config','--format','json'],cwd=root,env=case,capture_output=True,text=True,check=True)
    model=json.loads(result.stdout)
    for name in ['site-php-fpm','site-php-cli']:
        actual=model['services'][name]['environment']
        if actual.get('DOCS_MCP_HTTP_ENABLED')!=enabled or actual.get('DOCS_MCP_OAUTH_ISSUER')!=issuer:
            raise RuntimeError('Production FPM/CLI OAuth configuration differs.')
print('Production FPM/CLI share exact issuer and feature switch; unconfigured HTTP stays disabled.')
