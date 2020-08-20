#!/bin/bash

/opt/php_7_4_6/bin/php mpl.php src dst

if [ "0" -eq "$?" ];then
    bash output.log
fi


