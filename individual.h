#ifndef INDIVIDUAL
#define INDIVIDUAL

struct individual {
    int value;
};

struct individual *individual_create(void);

double individual_fitness(struct individual *individual_for_calculate);

void individual_dump(struct individual *individual_for_dump);

void individual_destory(struct individual *individual_for_destory);

#endif

