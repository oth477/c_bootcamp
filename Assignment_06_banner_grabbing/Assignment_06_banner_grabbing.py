import socket
import time

# Common web ports that require an initial HTTP request before responding
HTTP_PORTS = {80, 8080, 8000, 8888}

def grab_banner(target_ip: str, port: int, timeout: float = 2.0) -> tuple[bool, str]:
    """
    Connects to a specific TCP port and attempts to retrieve the service banner.
    Returns a tuple: (is_port_open, banner_string)
    """
    try:
        # Create an IPv4 TCP socket using a context manager for automatic cleanup
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            s.settimeout(timeout)
            
            # 1. Attempt TCP connection; return immediately if the port is closed
            if s.connect_ex((target_ip, port)) != 0:
                return (False, "Port is closed or filtered")
            
            # 2. Handle interactive web services by sending an initial HTTP HEAD request
            if port in HTTP_PORTS:
                http_request = f"HEAD / HTTP/1.1\r\nHost: {target_ip}\r\nConnection: close\r\n\r\n"
                s.sendall(http_request.encode('utf-8'))
            
            # 3. Receive up to 1024 bytes of banner data from the service
            raw_data = s.recv(1024)
            if not raw_data:
                return (True, "No banner returned (Empty response)")
            
            # 4. Decode bytes safely, ignoring non-UTF-8 binary characters
            decoded_banner = raw_data.decode('utf-8', errors='ignore').strip()
            if not decoded_banner:
                return (True, "No printable banner found")
            
            # 5. Extract the primary first line of the banner
            lines = decoded_banner.splitlines()
            first_line = lines[0].strip()
            
            # For HTTP services, also look for the 'Server:' header to capture version details
            if port in HTTP_PORTS:
                for line in lines[1:]:
                    if line.lower().startswith("server:"):
                        first_line = f"{first_line} | {line.strip()}"
                        break
                        
            return (True, first_line)

    except socket.timeout:
        # The TCP handshake succeeded (port is open), but the service sent no data within the timeout
        return (True, "No banner received (Connection timed out waiting for data)")
    except socket.error as err:
        return (False, f"Socket error: {err}")

def main():
    print("=" * 65)
    print("       TCP Service Fingerprinting & Banner Grabbing Tool")
    print("=" * 65)
    
    target = input("[?] Enter Target IP or Hostname (e.g., 127.0.0.1): ").strip()
    
    # Validate and resolve target hostname to IPv4
    try:
        target_ip = socket.gethostbyname(target)
    except socket.gaierror:
        print(f"[!] Error: Could not resolve hostname or reach target '{target}'.")
        return

    # Standard ports to inspect: FTP(21), SSH(22), SMTP(25), HTTP(80), POP3(110), IMAP(143), MySQL(3306), HTTP-Alt(8080)
    target_ports = [21, 22, 25, 80, 110, 143, 3306, 8080]
    timeout = 2.0

    print(f"\n[*] Target IP   : {target_ip}")
    print(f"[*] Target Ports: {target_ports}")
    print(f"[*] Timeout     : {timeout}s per port")
    print("-" * 65)

    start_time = time.perf_counter()
    open_ports_count = 0

    # Iterate through target ports and grab banners
    try:
        for port in target_ports:
            is_open, banner_info = grab_banner(target_ip, port, timeout)
            if is_open:
                open_ports_count += 1
                print(f"[+] Port {port:<5} [OPEN]   --> Banner: {banner_info}")
            else:
                print(f"[-] Port {port:<5} [CLOSED] --> {banner_info}")
                
    except KeyboardInterrupt:
        print("\n[!] Execution interrupted manually by user.")
        return

    elapsed_time = time.perf_counter() - start_time
    print("-" * 65)
    print(f"[*] Reconnaissance completed in: {elapsed_time:.2f} seconds.")
    print(f"[*] Open ports discovered: {open_ports_count}/{len(target_ports)}")
    print("=" * 65)

if __name__ == "__main__":
    main()