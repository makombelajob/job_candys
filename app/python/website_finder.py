import sys
import requests


def check_domain(domain):

    try:
        response=requests.get(
            f"https://{domain}",
            timeout=5,
            allow_redirects=True
        )

        if response.status_code<400:
            return f"https://{domain}"

    except requests.RequestException:
        pass

    return None


def find_website(name):

    clean=name.lower().strip()

    variants=[
        clean.replace(" ",""),
        clean.replace(" ","-"),
        clean.replace("-",""),
    ]

    for variant in variants:

        for extension in [".com",".fr",".eu"]:

            domain=variant+extension

            website=check_domain(domain)

            if website:
                return website

    return None


if __name__=="__main__":

    company=sys.argv[1]

    result=find_website(company)

    print(result or "Aucun site trouvé")