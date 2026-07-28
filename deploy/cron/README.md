# Instalação da sincronização automática do PPA

## Arquivo para o sysadmin

Enviar:

`deploy/cron/install-ppa-sync-cron.sh`

O script não contém credenciais e não lê nem imprime o conteúdo do `.env`.

## Instalação

No servidor em que o projeto está publicado:

```bash
sudo bash install-ppa-sync-cron.sh /var/www/projects/observatoriosasc
```

O instalador:

1. exige execução como `root`;
2. confirma que `/etc/cron.d`, PHP, projeto e usuário `www-data` existem;
3. testa a escrita no cache como `www-data`;
4. valida a ajuda do comando de sincronização como `www-data`;
5. instala `/etc/cron.d/observatoriosasc-ppa` com modo `0644`;
6. imprime a regra instalada.

O horário configurado é 04:15 todos os dias em `America/Sao_Paulo`.

## Verificação

```bash
sudo cat /etc/cron.d/observatoriosasc-ppa
sudo -u www-data /usr/bin/php \
  /var/www/projects/observatoriosasc/bin/ppa-scheduled-sync.php --help
```

Depois da primeira execução automática, verificar no painel `/admin/ppa` se a
data da última atualização mudou.

## Remoção da regra

```bash
sudo rm /etc/cron.d/observatoriosasc-ppa
```

A remoção da regra não apaga caches nem resultados já consolidados.
