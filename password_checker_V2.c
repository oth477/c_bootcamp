#include <stdio.h>
#include <stdbool.h>
#include <strings.h>
#include <ctype.h>
#include <stdlib.h>
// password cherk V2. made by Othman

#define TXT_READ "r"


int password_cheker(){

    // 0 ==> weak password
    // 1 ==> strong password
    // 2 ==> bad common password
    // 3 ==> medium password

    char password[50] = "\n";
    bool is_common_password = false;

    printf("please enter your password: ");
    fgets(password, sizeof(password), stdin);

    FILE *filePointer = fopen("C:\\Users\\Othman\\Desktop\\bootcamp\\programming\\c_bootcamp\\session_03(26-20-2026)\\rockyou.txt", TXT_READ);

    if(filePointer == NULL){

        printf("Could not read the file\n");
        exit(-1);
    }

    char buffer[4096] = {0};

    while(fgets(buffer, sizeof(buffer), filePointer) != NULL){

        if(strcmp(password, buffer) == 0){  

            is_common_password = true;
            return 2;  // 2  ==> bad common password

        }

    }

    fclose(filePointer);


    // password features
    // -1 --> to ingnore the '\n', when you enter "enter"
    int length = strlen(password) -1;
    int has_upper = 0;
    int has_lower = 0;
    int has_digit = 0;
    int has_special = 0;
    int is_length_ok = 0;

    int score = 0;

    for(int i = 0; i < length ; i++){

        if(isupper(password[i])){
            has_upper = 1;
            printf("has_upper if  \n");
        } else if(islower(password[i])){
            printf("has_lower if \n");
            has_lower = 1;
        } else if(isdigit(password[i])){
            printf("has_digit if\n");
            has_digit = 1;
        } else {
            printf("has_special\n");
            has_special = 1;
        }
    }

    if(length >= 8){
        printf("length is: %d\n", length);
        printf("is_length_ok if\n");
        is_length_ok = 1;
    }


    score = has_digit + has_lower + has_special + has_upper + is_length_ok;

    // 0 ==> weak password    == if(!is_length_ok)
    // 1 ==> strong password  == if (score == 5)
    // 2 ==> bad common password
    // 3 ==> medium password  == else
    if(!length || score <= 2){

        printf("Password score is: %d\n", score);
        return 0;
    } else if(score > 2 && score < 5){
        printf("Password score is: %d\n", score);
        return 3;
    } else if(score == 5){
        printf("Password score is: %d\n", score);
        return 1;
    }
}




int main(){

    int password_status = password_cheker();

    if(password_status == 2 ){

        printf("you password is a common password.\nIt is easy for attacker to guess it.\n"); // done
        
    } else if(password_status == 0){

        printf("your password is weak\n");  // done
        
    } else if(password_status == 3){
        
        printf("mour Password is medium");  // done
        
    } else if(password_status == 1) {
        printf("your password is strong\n");  // done
    }

    return 0;
}
