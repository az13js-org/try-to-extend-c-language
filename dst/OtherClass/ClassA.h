#ifndef SRC_OTHERCLASS_CLASSA
#define SRC_OTHERCLASS_CLASSA
#include <malloc.h>
#include <stdlib.h>
#include <stdio.h>

struct src_otherclass_classa_attributes {
    int a;
};

struct src_otherclass_classa_attributes *src_otherclass_classa_new(int input);

void src_otherclass_classa_destory(struct src_otherclass_classa_attributes *src_otherclass_classa_for_distory);


#endif
