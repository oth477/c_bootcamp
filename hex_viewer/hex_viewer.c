#include <stdio.h>
#include <stdlib.h>

// define Macro
#define CHUNK_SIZE 16

int hex_viewer(const char *filename){

    FILE *file = fopen(filename, "rb");
    
    if(file == NULL){
        printf("sorry we could not read the file\n");
        return -1; // return -1 as an error sign
    }

    unsigned char buffer[CHUNK_SIZE];
    size_t bytes_read; //the bytes we read
    // the current address (the first 16 is 00000000, the second is 00000010)
    unsigned long offset = 0; 


    printf("----------+-------------------------------------------------+-----------------\n");
    printf("Offset    | Hexadecimal Representation                      | ASCII\n");
    printf("----------+-------------------------------------------------+-----------------\n");

    // if the function "fread" reach the end of the executable file, the "fread" return 0
    while((bytes_read = fread(buffer, 1, CHUNK_SIZE, file)) > 0){
        //08 (8 boxes and fill the empty one with 0). l -> lnog, X -> hex
        printf("%08lX   |   " , offset);

        // hex data loop
        for(size_t i = 0; i < CHUNK_SIZE; i++){
            if(i < bytes_read){
                printf("%02X ", buffer[i]);
            }
            else{
                // if we ran out of bytes before we ran out the 16 bytes. it happens offtin at the end of the file
                printf("   ");
            }
        }

        printf("| ");

        // ASCII data loop
        for(size_t i = 0; i < bytes_read; i++){
            // check if the character is printable 
            if(buffer[i] >= 32 && buffer[i] <= 126){
                printf("%c", buffer[i]);
            } 
            else {
                printf(".");
            }
        }

        printf("\n");
        offset += bytes_read;
    }


    fclose(file);
    return 0;

}

// argc ---> Argument Count  = (number of arguments in execution command)
// argv ---> Argument Vector = (arguments array whre argv[0] = the program itself, argv[1] = filename)
int main(int argc, char *argv[]){

    if(argc < 2) {
        fprintf(stderr, "usage : %s <exe filename>\n", argv[0]);

        return 1;
    }


    if(hex_viewer(argv[1]) != 0){
        return 1;
    }

    return 0;
}
