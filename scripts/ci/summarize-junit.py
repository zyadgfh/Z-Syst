#!/usr/bin/env python3
"""Expose a bounded summary when CI logs are unavailable; never change test status."""
import collections
import os
import sys
import xml.etree.ElementTree as ET

path = sys.argv[1]
if not os.path.isfile(path):
    print('::warning::PHPUnit did not produce a JUnit report')
    sys.exit(0)
try:
    root = ET.parse(path).getroot()
except ET.ParseError as error:
    print(f'::warning::Invalid JUnit report: {error}')
    sys.exit(0)
cases = root.findall('.//testcase')
counts = collections.Counter()
examples = {}
for case in cases:
    for kind in ('failure', 'error', 'skipped'):
        node = case.find(kind)
        if node is not None:
            counts[kind] += 1
            text = (node.get('message') or node.text or '').strip()
            lines = [line.strip() for line in text.splitlines() if line.strip()]
            reason = ' | '.join([line for line in lines if not line.startswith(('Stack trace', '#'))][:5]) or kind
            key = (kind, reason[:300])
            examples.setdefault(key, (case.get('classname', ''), case.get('name', '')))
            break
print(f"::notice::PHPUnit cases={len(cases)} failures={counts['failure']} errors={counts['error']} skipped={counts['skipped']}")
for (kind, reason), (classname, name) in list(examples.items())[:20]:
    reason = reason.replace('%', '%25').replace('\r', '%0D').replace('\n', '%0A')
    print(f'::warning::{kind} {classname}::{name}: {reason}')
