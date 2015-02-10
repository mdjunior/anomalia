anomalia
========

anomalia is a plugin for nfsen that detects anomalies in TCP/UDP connections based on the flow analysis.

anomalia é um plugin para NFSen que detecta anomalias de conexões TCP/UDP com base em análise de flows.

Como instalar

Verifique se os pacotes perl-DBI e perl-DBD-SQLite estao instalados.
Copie os arquivos dentro da pasta backend para sua instalacao do NFSEN. No OpenBSD essa pasta fica em /usr/local/lib/nfsen/plugins/. A estrutura da pasta ficara:

/usr/local/lib/nfsen/plugins/
- anomalia.pm
- anomalia/anomalia.db

Voce tambem pode alterar a localizacao da base de dados, caso seja de sua preferencia. Voce devera colocar no seu arquivo de configuracao a localizacao dela.

Em /etc/nfsen.conf (a localizacao pode ser diferente dependendo de sua instalacao)

Insira em @plugins a linha:
    [ 'live',  'anomalia' ],
    
Devera ficar assim:

@plugins = (
    [ 'live',  'PortTracker' ], 
    [ 'live',  'anomalia' ],
);

Voce tambem devera configurar a localizacao do arquivo de banco de dados. Para isso, voce devera incluir a linha abaixo no %PluginConf.

anomalia => {    db_file         => "$BACKEND_PLUGINDIR/anomalia/anomalia.db"},

Agora copie os arquivos dentro da pasta frontend no seu diretorio de plugins web, provavelmente /var/www/htdocs/nfsen/plugins/.

Reinicie o nfsen e vá na aba plugins.
