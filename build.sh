#!/bin/bash

# Auto build program

function try_run() {
    if [ -e main ];then
        ./main
    else
        echo "Miss \"main\"."
    fi
}

GCC_FILE=""
function find_gcc_file() {
    GCC_LIST=("/opt/gcc_9/bin/gcc" "/usr/bin/gcc")
    for gcc_file in ${GCC_LIST[@]}
    do
        if [ -e "$gcc_file" ];then
            GCC_FILE=$gcc_file
        fi
    done
}

find_gcc_file
if [ -e "$GCC_FILE" ];then
    echo "gcc=$GCC_FILE"
    $GCC_FILE -S -O3 -o main.s main.c
    $GCC_FILE -S -O3 -o individual.s individual.c
    $GCC_FILE -O3 -o main main.c individual.c
    try_run
else
    echo "Miss gcc!"
fi



