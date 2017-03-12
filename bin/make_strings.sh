#!/bin/bash

root_dir="$(cd $(dirname $(dirname "${BASH_SOURCE[0]}")) && pwd)"

echo '<?php'
echo
echo '$strings = [';
grep -Pro '\$this->_\(.+?\)' $root_dir | while read -r hit ; do
    path=$(echo "$hit" | cut -d: -f1)
    path=${path:$((${#root_dir}+1))}
    line=$(echo "$hit" | cut -d: -f2-)
    string=${line:9}
    string=${string::-1}
    string=$(echo $string | sed -e 's@, __CLASS__@@g')
    key=$string
    ns=$(echo $path | sed 's@lib/@@g' | sed 's@/@_@g' | sed 's@.php@@g')
    ns="${ns}:"
    key="${string:0:1}${ns}${string:1}"
    echo -n '    '
    echo -n $key
    echo -n ' => '
    echo -n $string
    echo ','
done
echo '];'
