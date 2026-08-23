from app.services.verification import VerificationDetector


def test_detects_unusual_traffic():
    detector = VerificationDetector()
    assert detector.page_requires_verification("Our systems have detected unusual traffic from your computer network")


def test_detects_recaptcha_copy():
    detector = VerificationDetector()
    assert detector.page_requires_verification("Please complete the reCAPTCHA to continue")


def test_normal_maps_html_is_not_verification():
    detector = VerificationDetector()
    assert detector.page_requires_verification("Dentists in Lahore · Google Maps") is False
    assert detector.page_requires_verification("Please enable JavaScript to view Google Maps") is False


def test_sorry_url_is_verification():
    detector = VerificationDetector()
    assert detector.page_requires_verification("", "https://www.google.com/sorry/index?continue=maps")
