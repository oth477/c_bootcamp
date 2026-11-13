import socket
import time
from concurrent.futures import ThreadPoolExecutor, as_completed



def scan_port(target_ip: str, port: int, timeout: float = 1.0) -> tuple[int, bool, str]:
    """
    Scans a single TCP port on the specified target IP.
    Returns a tuple: (port_number, is_open, service_name)
    """
    
    try:
        #  using a context manager (with) ensures the socket is automaticallly closed after scanning
        # AF_INET specifies IPv4, and SOCK_STREAM specifies TCP
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            s.settimeout(timeout)
            
            #  connect_ex returns 0 if the TCP 3-way Handshake succeeds, or an errno value if it fails
            result = s.connect_ex((target_ip, port))
            
            if result == 0:
                #  Attempt to resolve the standard service name associated with the port
                try:
                    service_name = socket.getservbyport(port, 'tcp')
                except OSError:
                    service_name = "Unknown"
                return (port, True, service_name)
                
    except socket.error:
        #   ignore transient socket errors to prevent individual worker threads from crashing
        pass
        
    return (port, False, "")





def main():
    print("=" * 55)
    print("       Multithreaded TCP Port Scanner")
    print("=" * 55)
    
    target = input("[?] Enter Target IP or Hostname (e.g., 127.0.0.1): ").strip()
    
    # 1. Validate target and resolve hostname to IPv4 address if necessary
    try:
        target_ip = socket.gethostbyname(target)
    except socket.gaierror:
        print(f"[!] Error: sorry could not resolve hostname or reach target '{target}'.")
        return




    # 2. Configure scan range and thread pool parameters
    start_port = 1
    end_port = 1024
    max_workers = 50
    timeout = 1.0  # 1 second timeout per port

    print(f"\n[*] Scanning Target: {target_ip}")
    print(f"[*] Port Range: {start_port}-{end_port} | Max Threads: {max_workers}")
    print("-" * 55)

    start_time = time.perf_counter()
    open_ports = []



    # 3. Launch concurrent threads using ThreadPoolExecutor
    try:
        with ThreadPoolExecutor(max_workers=max_workers) as executor:
            # Submit all port scan tasks to the thread pool and map each future to its port
            futures = {
                executor.submit(scan_port, target_ip, port, timeout): port 
                for port in range(start_port, end_port + 1)
            }
            
            # Process results immediately as each thread completes
            for future in as_completed(futures):
                port, is_open, service = future.result()
                if is_open:
                    print(f"[+] Port {port:<5} is OPEN   -->  Service: {service}")
                    open_ports.append(port)
                    
    except KeyboardInterrupt:
        print("\n[!] Scan interrupted manually by user.")
        return




    # 4. Calculate total elapsed time and display summary
    end_time = time.perf_counter()
    elapsed_time = end_time - start_time

    print("-" * 55)
    print(f"[*] scan completed in: {elapsed_time:.2f} seconds.")
    print(f"[*]total open ports found: {len(open_ports)}")
    if open_ports:
        print(f"[*]open ports (sorted): {sorted(open_ports)}")
    print("=" * 55)

if __name__ == "__main__":
    main()