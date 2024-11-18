import logging
import holidays

logging.basicConfig(filename='fetch_holidays_debug.log', level=logging.DEBUG)

class Ukraine(holidays.HolidayBase):
    def __init__(self, **kwargs):
        self.country = "UA"
        holidays.HolidayBase.__init__(self, **kwargs)

        # Define Ukrainian holidays here
        self[2024, 1, 1] = "New Year's Day"
        self[2024, 3, 8] = "International Women's Day"
        self[2024, 5, 1] = "Labour Day"
        self[2024, 5, 9] = "Victory Day"
        self[2024, 6, 28] = "Constitution Day"
        self[2024, 8, 24] = "Independence Day"

ua_holidays = Ukraine(years=2024)

for date, name in ua_holidays.items():
    logging.debug(f"Holiday: {name}, Date: {date}")
