#include "ClassA.h"



struct src_otherclass_classa_attributes *src_otherclass_classa_new(int input) {
    struct src_otherclass_classa_attributes *this = (struct src_otherclass_classa_attributes *)malloc(sizeof(struct src_otherclass_classa_attributes));
    if (NULL != this) {
        this->a = input;
        printf("object of ClassA: a = %d\n", this->a);
    }
    return this;


}

void src_otherclass_classa_destory(struct src_otherclass_classa_attributes *src_otherclass_classa_for_distory) {
    free(src_otherclass_classa_for_distory);
}

