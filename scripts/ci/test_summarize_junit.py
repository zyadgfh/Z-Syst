import pathlib
import subprocess
import sys
import tempfile
import unittest

SCRIPT = pathlib.Path(__file__).with_name('summarize-junit.py')


class SummaryTest(unittest.TestCase):
    def run_summary(self, contents):
        with tempfile.TemporaryDirectory() as directory:
            path = pathlib.Path(directory) / 'junit.xml'
            path.write_text(contents)
            return subprocess.check_output([sys.executable, SCRIPT, str(path)], text=True)

    def test_counts_failures_and_reports_reason(self):
        output = self.run_summary('''<testsuites><testsuite>
            <testcase classname="Checkout" name="race"><error>race
            Database unavailable</error></testcase>
            <testcase classname="Checkout" name="ok"/>
            <testcase classname="Checkout" name="skipped"><skipped/></testcase>
            </testsuite></testsuites>''')
        self.assertIn('cases=3 failures=0 errors=1 skipped=1', output)
        self.assertIn('Database unavailable', output)

    def test_invalid_report_does_not_mask_original_test_failure(self):
        output = self.run_summary('<broken')
        self.assertIn('Invalid JUnit report', output)


if __name__ == '__main__':
    unittest.main()
