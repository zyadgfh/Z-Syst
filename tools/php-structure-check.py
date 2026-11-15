#!/usr/bin/env python3
"""Structural sanity check for PHP files when no PHP binary is available.

This sandbox (and any CI job without php) cannot run `php -l`, so a broken brace
or an unterminated string is only discovered when a workflow fails. This checker
tokenises crudely — strings, heredocs and comments removed — then verifies:

  * the file starts with <?php
  * (), {} and [] balance, and every closer matches its opener
  * no stray carriage returns (a CRLF file committed from Windows breaks
    shebang lines and confuses diffs)
  * nothing is left open at EOF (an unterminated string is the usual cause)

It is a smoke test, not a parser: it will not catch a missing semicolon or a
wrong type. `php -l` in CI remains the authority; this only makes the failure
visible before pushing.

Usage: python3 tools/php-structure-check.py <file.php> [...]
"""
import re
import sys


def strip_code(src):
    """Return (code_only, end_state). end_state != 'code' means something is unterminated."""
    out = []
    i, n = 0, len(src)
    state = 'code'
    tag = ''
    while i < n:
        c = src[i]
        nxt = src[i + 1] if i + 1 < n else ''
        if state == 'code':
            if c == "'":
                state = 'sq'; i += 1; continue
            if c == '"':
                state = 'dq'; i += 1; continue
            if c == '/' and nxt == '/':
                state = 'lc'; i += 2; continue
            if c == '#':
                state = 'lc'; i += 1; continue
            if c == '/' and nxt == '*':
                state = 'bc'; i += 2; continue
            m = re.match(r'<<<\s*(\'?)([A-Za-z_][A-Za-z0-9_]*)\1', src[i:])
            if m:
                state, tag = 'hd', m.group(2); i += m.end(); continue
            out.append(c); i += 1; continue
        if state == 'sq':
            if c == '\\': i += 2; continue
            if c == "'": state = 'code'
            i += 1; continue
        if state == 'dq':
            if c == '\\': i += 2; continue
            if c == '"': state = 'code'
            i += 1; continue
        if state == 'lc':
            if c == '\n': state = 'code'; out.append('\n')
            i += 1; continue
        if state == 'bc':
            if c == '*' and nxt == '/': state = 'code'; i += 2; continue
            if c == '\n': out.append('\n')
            i += 1; continue
        if state == 'hd':
            if src.startswith(tag, i) and (i == 0 or src[i - 1] == '\n'):
                state = 'code'; i += len(tag); continue
            if c == '\n': out.append('\n')
            i += 1; continue
    return ''.join(out), state


def check(path):
    problems = []
    src = open(path, encoding='utf8', errors='replace').read()
    if '\r' in src:
        problems.append('contains CR bytes (CRLF line endings)')
    if not src.lstrip().startswith('<?php'):
        problems.append('does not start with <?php')
    code, end = strip_code(src)
    if end != 'code':
        problems.append(f'unterminated string/comment at EOF (state={end})')
    pairs = {'(': ')', '{': '}', '[': ']'}
    stack, line = [], 1
    for ch in code:
        if ch == '\n':
            line += 1
        elif ch in pairs:
            stack.append((ch, line))
        elif ch in pairs.values():
            if not stack:
                problems.append(f'unmatched {ch!r} at line {line}')
                break
            op, oline = stack.pop()
            if pairs[op] != ch:
                problems.append(f'{op} opened at line {oline} closed by {ch} at line {line}')
                break
    if stack and not any('unmatched' in p or 'opened at' in p for p in problems):
        op, oline = stack[-1]
        problems.append(f'{op} opened at line {oline} never closed ({len(stack)} still open)')
    return problems


def main(argv):
    bad = 0
    for p in argv:
        probs = check(p)
        if probs:
            bad += 1
            print(f'FAIL {p}')
            for x in probs:
                print(f'       - {x}')
    print(f'\nchecked {len(argv)} file(s), problems in {bad}')
    return 1 if bad else 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1:]))
