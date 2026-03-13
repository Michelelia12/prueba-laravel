#!/bin/bash

_artisan()
{
    local arg="${COMP_LINE#php }"

    case "$arg" in
        artisan*)
            if [ -f artisan ]; then
              COMP_WORDBREAKS=${COMP_WORDBREAKS//:}
              COMMANDS=`(php artisan --raw --no-ansi list | sed "s/[[:space:]].*//g")`
              COMPREPLY=(`compgen -W "$COMMANDS" -- "${COMP_WORDS[COMP_CWORD]}"`)
            fi
            ;;
        *)
            COMPREPLY=( $(compgen -o default -- "${COMP_WORDS[COMP_CWORD]}") )
            ;;
        esac

    return 0
}
complete -F _artisan php

_composer()
{
    local cur="${COMP_WORDS[COMP_CWORD]}"
    local cmd="${COMP_WORDS[0]}"
    if ($cmd > /dev/null 2>&1)
    then
        COMPREPLY=( $(compgen -W "$($cmd list --raw | cut -f 1 -d " " | tr "\n" " ")" -- $cur) )
    fi
}
complete -F _composer composer