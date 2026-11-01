// Toolchain smoke test.
//
// The original scaffold test pumped `MyApp` and asserted counter text from
// the Flutter starter template — it could never pass against this POS app
// (its main() wires network clients, storage and loading plugins before the
// first frame). This test intentionally validates only the toolchain: the
// package compiles and the widget pipeline renders, without coupling to app
// runtime dependencies. Flow-level tests should live behind mocked
// repositories — see PROJECT_KB.md roadmap.
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('renders a minimal Material app', (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: Center(child: Text('Z-Syst POS')),
        ),
      ),
    );

    expect(find.text('Z-Syst POS'), findsOneWidget);
  });
}
