#include <stdio.h>
#include "individual.h"

int main(void) {
    struct individual *my_individual;
    my_individual = individual_create();
    if (NULL == my_individual) {
        printf("Create individual fail!\n");
        return 0;
    }
    individual_dump(my_individual);
    individual_destory(my_individual);
    return 0;
}

