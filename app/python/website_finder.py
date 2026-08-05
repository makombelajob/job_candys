import sys
import requests


def check_domain(domain):

    try:
        response = requests.get(
            f"https://{domain}",
            timeout=5,
            allow_redirects=True,
            headers={
                "User-Agent": "Mozilla/5.0"
            }
        )

        if response.status_code < 400:
            return f"https://{domain}"

    except requests.RequestException:
        pass

    return None


def generate_variants(name):

    clean = name.lower().strip()
    clean = clean.replace("'", "")

    words = clean.split()

    variants = []

    # Nom complet
    variants.append(
        clean.replace(" ", "")
    )

    variants.append(
        clean.replace(" ", "-")
    )

    # Premier mot seulement si suffisamment long
    if words and len(words[0]) >= 4:
        variants.append(words[0])

    return list(dict.fromkeys(variants))


def find_website(name):

    variants = generate_variants(name)

    extensions = [
        ".com",
        ".fr",
        ".eu",
        ".bzh"
    ]

    for variant in variants:

        for extension in extensions:

            domain = variant + extension

            print(f"Test : {domain}", file=sys.stderr)

            website = check_domain(domain)

            if website:
                return website

    return None


if __name__ == "__main__":

    if len(sys.argv) < 2:
        print("Aucun nom fourni")
        sys.exit(1)

    company = sys.argv[1]

    result = find_website(company)

    print(result or "Aucun site trouvé")