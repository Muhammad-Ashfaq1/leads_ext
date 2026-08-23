from app.utils.prompt import maps_search_url, normalize_search_query
from app.utils.ssrf import is_safe_public_url


def test_normalize_find_dentists_in_lahore():
    assert normalize_search_query("Find dentists in Lahore") == "dentists in Lahore"


def test_normalize_roofing_prompt():
    assert (
        normalize_search_query("Find roofing companies in Houston Texas")
        == "roofing companies in Houston Texas"
    )


def test_normalize_strips_contact_tail():
    assert (
        normalize_search_query("Find restaurants in New York with phone numbers and websites")
        == "restaurants in New York"
    )


def test_maps_search_url_encodes_query():
    assert maps_search_url("oil change in UAE") == "https://www.google.com/maps/search/oil+change+in+UAE"


def test_ssrf_blocks_private_and_local():
    assert is_safe_public_url("http://127.0.0.1/secret") is False
    assert is_safe_public_url("http://localhost/admin") is False
    assert is_safe_public_url("http://192.168.1.10/") is False
    assert is_safe_public_url("file:///etc/passwd") is False
    assert is_safe_public_url("not-a-url") is False
