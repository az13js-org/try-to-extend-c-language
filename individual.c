#include <stdio.h>
#include <stdlib.h>
#include "individual.h"

struct individual *individual_create(void)
{
    struct individual *new_individual;
    new_individual = (struct individual *)malloc(sizeof(struct individual));
    if (NULL != new_individual) {
        new_individual->value = rand();
    }
    return new_individual;
}

double individual_fitness(struct individual *individual_for_calculate)
{
    return (double)(individual_for_calculate->value);
}

void individual_dump(struct individual *individual_for_dump)
{
    printf("Dump for individual:\n");
    printf("Address:%p\n", individual_for_dump);
    printf(
        "Size of individual:%ld\n",
        (unsigned long int)sizeof(struct individual)
    );
    printf("Fitness:%.30lf\n", individual_fitness(individual_for_dump));
}

void individual_destory(struct individual *individual_for_destory)
{
    free(individual_for_destory);
}
