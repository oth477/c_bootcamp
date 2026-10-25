#include <stdio.h>
#include <stdbool.h>
#include <string.h>

// student name: Othman Al-Qadasi
int main(){

    // Strong password attrbutes 
    bool has_numbers = false;  // done
    bool has_small_letters = false;  // done
    bool has_min_length = false;    // done   
    bool has_symbols = false;       // done
    bool has_captical_letters = false; // done

    size_t min_letngth = 8;
    char symbols[] = "!@#$\\%^&*(){}";
    
    char password[15] = "\0";

    printf("Please enter your password: ");
    fgets(password, sizeof(password), stdin);

    printf("your password is: %s\n", password);
    printf("password size is %d\n", sizeof(password));

    
    // condition loop
    size_t real_len = strlen(password);
    for(int i = 0; i < real_len; i++){
        // has_numbers condition check
        if(!has_numbers){
            if(password[i] >= '0' && password[i] <= '9'){
            has_numbers = true;
        }
        }

        // has_small_letters condition check
        if(!has_small_letters){
            if(password[i] >= 'a' && password[i] <= 'z'){
                has_small_letters = true;
            }

        }
        

        // has_captical_letters condition check
        if(!has_captical_letters){
            if(password[i] >= 'A' && password[i] <= 'Z'){
                has_captical_letters = true;
            }

        }

        // has_min_length condition check
        if(!has_min_length){
            if(strlen(password) >= min_letngth){
                has_min_length = true;
            }
        }


        // has_symbols conditoin check
        if(!has_symbols){
            if(strchr(symbols, password[i])){
                has_symbols = true;
            }
        }

    }


    if(has_captical_letters && has_min_length && has_numbers && has_small_letters && has_symbols){
        printf("you have a strong password\n");
    }else{
        printf("your password is weak, please try again\n");
    }




    return 0;
}