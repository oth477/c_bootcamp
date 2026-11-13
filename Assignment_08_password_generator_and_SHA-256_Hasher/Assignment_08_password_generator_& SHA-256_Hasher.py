import hashlib
import secrets
import string


def generate_secure_password(length: int = 16) -> str:
    """
    Generates a cryptographically strong random password of a specified length.
    Guarantees the inclusion of uppercase, lowercase, digits, and special characters.
    """
    if length < 8:
        raise ValueError("Password length must be at least 8 characters for security compliance.")

    # Combine all required character sets into a single pool
    alphabet = string.ascii_letters + string.digits + string.punctuation

    # Continuously generate until the password satisfies all complexity policies
    while True:
        password = "".join(secrets.choice(alphabet) for _ in range(length))
        
        if (any(char.islower() for char in password)
                and any(char.isupper() for char in password)
                and any(char.isdigit() for char in password)
                and any(char in string.punctuation for char in password)):
            return password


def compute_sha256_hash(text: str) -> str:
    """
    Computes the cryptographic SHA-256 hash of a given string.
    Returns the 64-character hexadecimal digest.
    """
    # Encode the text string into raw UTF-8 bytes before passing to SHA-256
    encoded_bytes = text.encode("utf-8")
    
    # Create the SHA-256 hash object and return the hexadecimal representation
    hash_object = hashlib.sha256(encoded_bytes)
    return hash_object.hexdigest()


def main():
    print("=" * 70)
    print("       Cryptographic Password Generator & SHA-256 Hasher")
    print("=" * 70)

    user_input = input("[?] Enter desired password length (minimum 8, default 16): ").strip()
    
    # Validate user input for password length
    if not user_input:
        length = 16
    else:
        try:
            length = int(user_input)
        except ValueError:
            print("[!] Error: Invalid number entered. Please enter an integer value.")
            return

    try:
        # 1. Generate the cryptographically secure password
        generated_password = generate_secure_password(length)
        
        # 2. Compute the SHA-256 cryptographic hash of the generated password
        password_hash = compute_sha256_hash(generated_password)

        # 3. Display the results
        print("-" * 70)
        print(f"[+] Password Length      : {length} characters")
        print(f"[+] Generated Password   : {generated_password}")
        print(f"[+] SHA-256 Hash         : {password_hash}")
        print(f"[+] Hash Length          : {len(password_hash)} hex digits (256 bits)")
        print("=" * 70)

    except ValueError as err:
        print(f"[!] Security Policy Error: {err}")


if __name__ == "__main__":
    main()