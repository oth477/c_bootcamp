import hashlib
import time
from datetime import datetime


def calculate_file_hash(file_path: str, chunk_size: int = 4096) -> str | None:
    """
    Reads a file in binary mode using fixed-size chunks and calculates its SHA-256 hash.
    Returns the 64-character hexadecimal digest, or None if the file cannot be accessed.
    """
    sha256_hash = hashlib.sha256()

    try:
        # 1. Open the file in 'rb' (Read Binary) mode to process raw bytes accurately
        with open(file_path, "rb") as file:
            # Read the file in chunks (4096 bytes) to maintain low memory usage on large files
            while True:
                chunk = file.read(chunk_size)
                if not chunk:
                    break
                sha256_hash.update(chunk)
                
        return sha256_hash.hexdigest()

    except (FileNotFoundError, PermissionError, OSError):
        # Return None safely instead of crashing if the file is deleted or locked
        return None


def start_fim_monitor(file_path: str, interval: int = 3) -> None:
    """
    Establishes an initial cryptographic baseline hash and continuously monitors
    the target file for any unauthorized modifications.
    """
    print("\n" + "=" * 75)
    print(f"[*] Initializing File Integrity Monitoring (FIM) on: {file_path}")
    print("=" * 75)

    # 2. Compute and store the initial Baseline Hash
    baseline_hash = calculate_file_hash(file_path)
    if baseline_hash is None:
        print(f"[!] Error: Unable to establish baseline. File '{file_path}' not found or inaccessible.")
        return

    init_time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"[+] [{init_time}] Baseline Hash Established Successfully.")
    print(f"[+] Baseline SHA-256 : {baseline_hash}")
    print(f"[*] Polling Interval : Every {interval} seconds (Press Ctrl+C to stop)")
    print("-" * 75)

    # 3. Continuous monitoring loop
    try:
        while True:
            # Pause execution to prevent high CPU utilization (Busy-Waiting)
            time.sleep(interval)

            current_hash = calculate_file_hash(file_path)
            timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

            # Handle the case where the monitored file is deleted or moved during runtime
            if current_hash is None:
                print(
                    f"[CRITICAL ALERT] [{timestamp}] Target file '{file_path}' "
                    f"is MISSING or inaccessible!"
                )
                continue

            # 4. Compare current hash against the stored baseline hash
            if current_hash != baseline_hash:
                # 5. Trigger immediate Critical Security Alert upon mismatch
                print("\n" + "!" * 75)
                print(f"[CRITICAL ALERT] [{timestamp}] UNAUTHORIZED FILE MODIFICATION DETECTED!")
                print(f"    Target File   : {file_path}")
                print(f"    Baseline Hash : {baseline_hash}")
                print(f"    Modified Hash : {current_hash}")
                print("!" * 75 + "\n")

                # Update baseline to the new state so subsequent changes are also tracked cleanly
                baseline_hash = current_hash

    except KeyboardInterrupt:
        print("\n" + "-" * 75)
        print("[*] File Integrity Monitoring stopped manually by administrator.")
        print("=" * 75)


def main():
    print("=" * 75)
    print("           File Integrity Monitoring (FIM) - Incident Response Tool")
    print("=" * 75)

    target_file = input("[?] Enter the sensitive file path to monitor (e.g., config.txt): ").strip()

    if not target_file:
        print("[!] Error: No file path provided.")
        return

    start_fim_monitor(target_file, interval=3)


if __name__ == "__main__":
    main()