from app.services.parser import (
    DuplicateTracker,
    extract_coordinates,
    extract_emails,
    extract_place_id,
    parse_lead,
)


def test_parse_lead_requires_name():
    assert parse_lead({"address": "Lahore"}) is None


def test_parse_lead_keeps_missing_fields_null():
    lead = parse_lead({"business_name": "Example Dental Clinic"})
    assert lead is not None
    assert lead.phone is None
    assert lead.emails == []
    assert lead.website is None
    assert lead.rating is None


def test_parse_lead_does_not_fabricate_coordinates():
    lead = parse_lead({"business_name": "Clinic", "address": "Lahore"})
    assert lead.latitude is None
    assert lead.longitude is None


def test_extract_emails_filters_junk():
    text = "Contact hello@clinic.com and logo@cdn.example.com and user@sentry.io and photo@site.com.png"
    emails = extract_emails(text)
    assert "hello@clinic.com" in emails
    assert "user@sentry.io" not in emails


def test_extract_place_id_and_coords_from_maps_url():
    url = "https://www.google.com/maps/place/Clinic/@31.5204,74.3587,17z/data=!1s0x391905abc:0xaaa123"
    assert extract_place_id(url) == "0x391905abc:0xaaa123"
    assert extract_coordinates(url) == (31.5204, 74.3587)


def test_duplicate_tracker_uses_place_id_then_name_address():
    tracker = DuplicateTracker()
    first = parse_lead(
        {
            "business_name": "Clinic A",
            "address": "Lahore",
            "place_id": "place-1",
        }
    )
    second = parse_lead(
        {
            "business_name": "Clinic A Different Label",
            "address": "Somewhere else",
            "place_id": "place-1",
        }
    )
    third = parse_lead({"business_name": "Clinic B", "address": "Karachi"})
    fourth = parse_lead({"business_name": "Clinic B", "address": "Karachi"})
    assert first and second and third and fourth
    assert tracker.is_duplicate(first) is False
    assert tracker.is_duplicate(second) is True
    assert tracker.is_duplicate(third) is False
    assert tracker.is_duplicate(fourth) is True


def test_malformed_rating_is_ignored():
    lead = parse_lead({"business_name": "Clinic", "rating": "not-a-number", "review_count": "abc"})
    assert lead is not None
    assert lead.rating is None
    assert lead.review_count is None
