#ifndef SRC_TEST
#define SRC_TEST
#include <malloc.h>
#include <stdlib.h>
#include <stdio.h>
#include "OtherClass/ClassA.h"

struct src_test_attributes {

};

struct src_test_attributes *src_test_new(void);

void src_test_destory(struct src_test_attributes *src_test_for_distory);

void src_test_theend(struct src_test_attributes *this);


#endif
