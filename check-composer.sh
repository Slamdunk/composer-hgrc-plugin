#!/bin/sh

$HG status --modified --no-status --rev $HG_PARENT1 | grep composer.lock > /dev/null

if [ "$?" -lt 1 ]
then
    echo -e "\033[37;1;41m! ATTENZIONE ! \033[0;1;36m Le dipendenze vanno aggiornate, eseguire \"composer.phar install\"\033[0m"
fi
