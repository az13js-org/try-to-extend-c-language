#include "Test.h"
#include "OtherClass/ClassA.h"


int main(void) {
    struct src_test_attributes *this = (struct src_test_attributes *)malloc(sizeof(struct src_test_attributes));
    if (NULL != this) {
        struct src_otherclass_classa_attributes * testObject = src_otherclass_classa_new(100);
        this->theEnd();
        free(this);
    }
    return 0;
}


struct src_test_attributes *src_test_new(void) {

}

void src_test_destory(struct src_test_attributes *src_test_for_distory) {
    free(src_test_for_distory);
}

void src_test_theend(struct src_test_attributes *this) {
    printf("Finish\n");
}

