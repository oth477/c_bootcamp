import re

# 1. Pre-compile regular expressions with re.IGNORECASE for performance and evasion resistance
ATTACK_PATTERNS = {
    "SQL Injection (SQLi)": re.compile(
        r"(union\s+select|select\s+.*\s+from|or\s+1=1|'--\s*|;\s*drop\s+table)",
        re.IGNORECASE
    ),
    "Path Traversal": re.compile(
        r"(\.\./|\.\.\\|/etc/passwd|/etc/shadow|c:\\windows\\system32)",
        re.IGNORECASE
    ),
    "Failed Login / Brute Force": re.compile(
        r"(event\s*id[:=]\s*4625|\b4625\b|login\s+failed|failed\s+password|authentication\s+failure)",
        re.IGNORECASE
    )
}

# Pre-compile a regex pattern to extract IPv4 addresses from log lines
IP_PATTERN = re.compile(r"\b(?:\d{1,3}\.){3}\d{1,3}\b")


def analyze_log_file(file_path: str) -> None:
    """
    Reads a log file line by line, applies regex detection rules, prints SOC alerts,
    and outputs a final statistical summary.
    """
    # Dictionary to track the count of each detected attack category
    attack_summary = {attack_type: 0 for attack_type in ATTACK_PATTERNS}
    total_lines = 0
    total_alerts = 0

    print("\n" + "=" * 75)
    print(f"[*] Starting Automated SOC Log Analysis on: {file_path}")
    print("=" * 75)

    try:
        # 2. Read the log file line by line using 'with open()' context manager
        with open(file_path, "r", encoding="utf-8", errors="ignore") as log_file:
            for line_number, line in enumerate(log_file, start=1):
                total_lines += 1
                clean_line = line.strip()
                
                if not clean_line:
                    continue

                # 3. Check each log line against all compiled attack patterns
                for attack_type, pattern in ATTACK_PATTERNS.items():
                    if pattern.search(clean_line):
                        # Extract the attacker's IP address if present in the log line
                        ip_match = IP_PATTERN.search(clean_line)
                        attacker_ip = ip_match.group(0) if ip_match else "Unknown IP"

                        # 4. Print a formatted security alert immediately
                        print(
                            f"[ALERT] Line {line_number:<4} | "
                            f"Type: {attack_type:<26} | "
                            f"Attacker IP: {attacker_ip}"
                        )

                        # Update counters for the final summary
                        attack_summary[attack_type] += 1
                        total_alerts += 1

    except FileNotFoundError:
        print(f"[!] Error: The file '{file_path}' was not found.")
        return
    except PermissionError:
        print(f"[!] Error: Permission denied while trying to read '{file_path}'.")
        return
    except OSError as err:
        print(f"[!] File system error: {err}")
        return

    # 5. Display final statistical summary report
    print("-" * 75)
    print("                     SOC ANALYSIS SUMMARY REPORT")
    print("-" * 75)
    print(f"[*] Total Log Lines Processed : {total_lines}")
    print(f"[*] Normal Lines Ignored      : {total_lines - total_alerts}")
    print(f"[*] Total Threats Detected    : {total_alerts}")
    print("\n[*] Breakdown by Attack Category:")
    
    for attack_type, count in attack_summary.items():
        print(f"    - {attack_type:<28}: {count}")
        
    print("=" * 75)


def main():
    print("=" * 75)
    print("             Automated Security Log Analyzer (SOC Tool)")
    print("=" * 75)
    
    log_path = input("[?] Enter the log file path (e.g., file.log): ").strip()
    
    if not log_path:
        print("[!] Error: No file path provided.")
        return

    analyze_log_file(log_path)


if __name__ == "__main__":
    main()