#include <stdio.h>
#include <stdlib.h>
#include <string.h>

// SOC Log Analyzer Function
void log_analyzer(const char *filename) {
    const char *keyword1 = "Failed";
    const char *keyword2 = "4625";

    // Allocate a 512-byte buffer to avoid high memory consumption
    char line[512];
    int line_number = 0;
    int alert_count = 0;

    // 1. Open the target log file in read mode ("r")
    FILE *file = fopen(filename, "r");
    if (file == NULL) {
        printf("[-] Error: Could not open file (%s). Make sure it exists in the same directory.\n", filename);
        return;
    }

    printf("==============================================================\n");
    printf("                      Log Analyzer Started                    \n");
    printf("==============================================================\n");
    printf("[*] Scanning file: %s\n", filename);
    printf("[*] Target keywords: [%s], [%s]\n\n", keyword1, keyword2);

    // 2. Read the file line by line using fgets
    while (fgets(line, sizeof(line), file) != NULL) {
        line_number++;

        // Remove trailing newline character for clean output
        line[strcspn(line, "\r\n")] = '\0';

        // 3. Search for the target keywords in each line using strstr
        if (strstr(line, keyword1) != NULL || strstr(line, keyword2) != NULL) {
            
            // 4. Increment alert counter and print line number and content
            alert_count++;
            printf("[!] Alert #%d | Line %d: %s\n", alert_count, line_number, line);
        }
    }

    // Close the file to free resources
    fclose(file);

    // 5. Display final summary of detected suspicious logs
    printf("\n==============================================================\n");
    printf("                      Final Scan Report                       \n");
    printf("==============================================================\n");
    printf("[+] Total lines scanned   : %d\n", line_number);
    printf("[!] Total alerts detected : %d\n", alert_count);
    printf("==============================================================\n");
}

// argc ---> Argument Count  = (number of arguments in execution command)
// argv ---> Argument Vector = (arguments array whre argv[0] = the program itself, argv[1] = filename)
int main(int argc, char *argv[]) {
    // Check if the user passed the log file name when running the program
    if (argc < 2) {
        printf("[-] Usage: %s <log_file.txt>\n", argv[0]);
        return 1;
    }

    // Pass the filename from command-line argument (argv[1]) to the function
    log_analyzer(argv[1]);

    return 0;
}