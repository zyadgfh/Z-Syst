# OBEY Clean Code by Robert C. Martin

This file defines mandatory working rules for this repository. Follow these instructions before making any code, test, refactor, review, or documentation change.

## Priority and behavior

- Treat every unqualified rule in this file as `MUST`; treat `Prefer` as `SHOULD`; treat `Do not`, `Avoid`, and `Never` as `MUST NOT` unless the user explicitly overrides it.
- Prefer readability, maintainability, correctness, and safe change over cleverness or speed hacks.
- Optimize for the next human reader.
- When trade-offs exist, choose the option that reduces long-term complexity.
- Never preserve bad structure just because it already exists.
- Apply the Boy Scout Rule: leave touched code cleaner than you found it.

## Core clean code principles

- Write code primarily for humans, not just for execution.
- Keep code simple, direct, and easy to modify.
- Avoid accidental complexity.
- Avoid surprising behavior.
- Prefer explicit intent over implicit magic.
- Prefer local reasoning: a reader should understand code with minimal jumping across files.
- Reduce technical debt instead of moving it around.

## Naming rules

- Use intention-revealing names.
- Names must explain purpose, role, or behavior without requiring extra comments.
- Avoid misleading names, overloaded meanings, and visually confusable identifiers.
- Make distinctions meaningful. Do not create names that differ only cosmetically.
- Use pronounceable, searchable names.
- Avoid abbreviations unless they are established domain or platform standards.
- Avoid encodings in names, including type prefixes, implementation hints, and Hungarian notation.
- Avoid unnecessary context in identifiers.
- Add context through modules, classes, namespaces, or types when that is cleaner than longer names.
- Use one word per concept across the codebase.
- Do not use multiple synonyms for the same operation or concept.
- Do not reuse a familiar word for a different meaning.
- Class, type, and module names should be nouns or noun phrases.
- Function and method names should be verbs or verb phrases.
- Use problem-domain names for domain concepts.
- Use solution-domain names for technical concepts.
- Do not use cute, funny, cryptic, or private-joke names.

## Function rules

- Keep functions small.
- Each function must do one thing.
- A function should have one clear reason to change.
- Keep each function at one level of abstraction.
- Organize code top-down so readers see the high-level story before details.
- Prefer descriptive names over short names.
- Minimize the number of parameters.
- Avoid boolean flag parameters. Split behavior into separate functions instead.
- Avoid output parameters unless language conventions make them necessary.
- Eliminate hidden side effects.
- Separate commands from queries.
- A function that answers a question should not also mutate state.
- Prefer exceptions or explicit result types over ad hoc error codes, according to project language norms.
- Isolate error handling from main logic.
- Eliminate duplication aggressively.
- Prefer straightforward control flow over clever control flow.
- Refactor deep nesting into clearer structure.

## Comment rules

- Do not use comments to compensate for bad naming or bad structure.
- First improve the code, then decide whether a comment is still needed.
- Prefer self-explanatory code.
- Use comments only when they add information the code cannot express well.
- Good comment categories include:
  - legal or licensing requirements
  - non-obvious intent
  - important warnings or constraints
  - rationale for a surprising decision
  - clarification of external behavior or protocol assumptions
- Remove redundant, obsolete, obvious, noisy, and misleading comments.
- Do not narrate the code line by line.
- Keep comments precise and maintain them when code changes.
- Avoid TODO comments unless they are actionable, specific, and necessary.

## Formatting and structure

- Use consistent formatting across the repository.
- Format code to reveal structure and intent.
- Keep related concepts close together.
- Keep files, classes, and functions reasonably small.
- Use vertical ordering to tell the story from higher level to lower level.
- Use indentation to clarify scope, not to hide complexity.
- Avoid excessive line length when it hurts readability.
- Avoid decorative alignment that is brittle during edits.
- Preserve a layout that supports fast scanning.

## Objects, modules, and data structures

- Separate behavior-rich objects from plain data carriers intentionally.
- Do not mix data containers and business behavior arbitrarily.
- Hide implementation details behind clear interfaces.
- Expose behavior, not representation.
- Use DTO-like structures as simple carriers when appropriate.
- Avoid train-wreck call chains and unnecessary knowledge of internal structure.
- Respect loose coupling and local boundaries.
- Keep persistence, framework, and third-party details from obscuring business behavior or core logic.

## Class and module design

- Keep classes and modules small.
- Each class or module should have one primary responsibility.
- Favor high cohesion.
- Split classes that accumulate unrelated behavior.
- Organize code so likely changes remain local.
- Public APIs should be small, obvious, and hard to misuse.
- Prefer composition over complex inheritance unless inheritance is clearly the simpler and more stable model.
- Keep constructors and setup logic from overwhelming domain behavior.

## Error handling

- Design error handling deliberately.
- Keep the happy path easy to read.
- Provide enough context in error messages for diagnosis.
- Use error types or exception classes that support caller decisions.
- Do not return `null` or equivalent absence sentinels when a safer model exists.
- Do not pass `null` or equivalent invalid states unless the API explicitly models that case.
- Prefer exceptions, special cases, empty objects, or explicit optionality according to the codebase's language and conventions.
- Make resource cleanup and shutdown paths correct and visible.

## Boundaries and external dependencies

- Isolate third-party libraries behind local adapters or wrappers when practical.
- Avoid coupling core logic directly to unstable external APIs.
- Create narrow interfaces around dependencies.
- Add learning tests or focused integration tests for tricky external behavior.
- When a dependency does not exist yet, define interfaces from local needs, not from guesses about future implementations.

## System construction rules

- Separate constructing a system from using it.
- Keep object graph assembly, dependency injection, factories, and framework bootstrapping out of ordinary business behavior.
- Put startup wiring in an explicit main or composition area.
- Use factories when construction policy is meaningful or complex.
- Do not let cross-cutting concerns obscure ordinary code flow.
- Use standards, frameworks, proxies, or AOP-style mechanisms only when they add demonstrable value.
- Test-drive architectural decisions with executable slices, not only diagrams or configuration.
- Use domain-specific languages only when they make system intent clearer than general-purpose code.

## Tests

- Treat tests as production-quality code.
- Keep tests clean, readable, deterministic, and maintainable.
- A test should communicate one main idea.
- Prefer simple setup and clear assertions.
- Avoid brittle tests coupled to irrelevant implementation details.
- Tests should be fast when possible.
- Tests should be isolated and order-independent.
- Tests should be self-checking.
- Add or update tests for behavior changes, bug fixes, and significant refactors.
- Do not ship code changes without proportionate validation.
- When fixing a bug, add a test that would have caught it, when feasible.

## TDD and clean test rules

- Prefer writing a failing test before production code when the behavior can be specified clearly.
- Do not write production behavior beyond what a failing test or explicit requirement justifies.
- Keep tests small enough that a failure names one behavior or one concept.
- Prefer one assert or one conceptual assertion per test when that improves clarity.
- Use test names and test data that reveal the business or technical behavior under test.
- Build a small testing vocabulary or helper DSL when repeated setup hides intent.
- Keep test code clean; dirty tests reduce the ability to change production code safely.
- Avoid tests that require multiple manual steps to run.
- Use coverage patterns to find untested risk, not as a substitute for meaningful assertions.
- Treat ignored, flaky, or skipped tests as unresolved questions.

## Concurrency and async work

- Do not introduce concurrency unless it provides a real benefit.
- Prefer simpler sequential code when it is sufficient.
- Minimize shared mutable state.
- Prefer immutability, message passing, or clear ownership boundaries.
- Keep synchronized or locked sections as small as possible.
- Be explicit about shutdown, cancellation, timeouts, and cleanup.
- Test concurrent behavior carefully where it matters.
- Know the execution model before changing concurrent code.
- Avoid dependencies between synchronized methods.
- Get non-concurrent behavior correct before adding threading.
- Make threaded code pluggable and tunable when its policy or concurrency level may vary.
- Run concurrency-sensitive tests under varied thread counts, schedules, and platforms where practical.
- Treat spurious failures as possible concurrency defects until evidence says otherwise.

## Refactoring rules

- Refactor in small, safe steps.
- Preserve behavior while improving structure.
- First make it work, then make it right.
- Remove duplication, dead code, misleading abstractions, and special-case clutter.
- Rename aggressively when names are weak.
- Extract code when doing so improves cohesion and clarity.
- Inline abstractions that no longer earn their cost.
- Prefer the simplest design that passes all relevant tests.

## Emergent design and successive refinement

- Prefer designs that run all relevant tests, remove duplication, express intent, and use the fewest necessary classes and methods.
- Refine code through working drafts rather than expecting the first version to be clean.
- When code starts rough, keep improving names, structure, and tests until intent is clear.
- Do not start a grand redesign when incremental refinement can recover the design safely.
- Use the Boy Scout Rule on touched code, but keep cleanup proportional to the task.

## Smells to detect and eliminate

Actively look for and fix these issues when touching code:

- vague or misleading names
- duplicated logic
- oversized functions
- oversized classes or modules
- mixed abstraction levels
- hidden side effects
- boolean control flags
- long parameter lists
- deep nesting
- excessive conditionals that should be isolated or polymorphic
- comment-heavy code that should be refactored instead
- dead code and unused abstractions
- fragile tests
- environment-dependent tests without need
- unnecessary indirection
- accidental complexity
- coupling that spreads change broadly
- build or tests requiring more than one manual step
- code at the wrong level of abstraction
- base classes depending on derivatives
- transitive navigation through object internals
- artificial coupling between unrelated concepts
- hidden logical dependencies
- unimplemented obvious behavior
- incorrect boundary behavior
- overridden safeties
- magic numbers without named meaning
- negative conditionals that obscure intent
- ignored tests and insufficient boundary tests
- functions that require readers to understand an algorithm before they can trust the name

## Change Process

For every non-trivial task:

1. Understand the intent and affected behavior.
2. Identify the simplest correct change.
3. Improve names before adding comments.
4. Keep edits localized when possible.
5. Add or update tests as needed.
6. Run relevant validation.
7. Review the diff for readability, duplication, and unnecessary complexity.
8. Ensure the final code is cleaner than before.

## Implementation preferences

- Prefer explicit, boring, maintainable solutions.
- Prefer standard library and existing project patterns over new dependencies.
- Do not add a dependency unless it clearly reduces overall complexity.
- Reuse established project conventions unless they conflict with these rules or the user explicitly asks otherwise.
- Keep interfaces small.
- Keep state transitions obvious.
- Avoid premature optimization.
- Optimize only when there is evidence or a known requirement.

## Review checklist

Before finishing, verify all of the following:

- Names reveal intent.
- Functions are small and focused.
- Classes and modules have clear responsibilities.
- Comments are necessary and accurate.
- Error handling is explicit and useful.
- Duplication was removed where reasonable.
- Tests cover the changed behavior appropriately.
- The code reads cleanly from top to bottom.
- The design is simpler or at least not more complex than before.
- The change follows existing project conventions.

## Output Expectations

When making changes:

- Briefly explain what changed.
- State what tests or checks were run.
- Call out any unresolved risk, assumption, or trade-off.
- If a requested change conflicts with these rules, follow the user request but mention the conflict explicitly.

## Hard rules

- Do not introduce misleading names.
- Do not keep duplicated logic without a strong reason.
- Do not add comments where better code would remove the need.
- Do not mix querying with mutation without a strong reason.
- Do not silently broaden scope beyond the requested task.
- Do not leave touched code less readable than before.

and

# OBEY Code Complete by Steve McConnell

## Purpose

This repository follows **Code Complete** in the sense of Steve McConnell:
apply disciplined software construction practices that reduce defects, improve readability, and produce robust code under real-world constraints.

All code generation, edits, and reviews must optimize for:
- low-defect construction
- readable and intention-revealing code
- controlled complexity
- defensive programming where appropriate
- strong routine and class design
- practical correctness over style theater

This file is a binding engineering policy: `MUST` is binding, `SHOULD` is a strong default, and `MUST NOT` is forbidden.

---

## Primary Directive

Construction quality is not accidental.

When uncertain, choose the option that:
1. lowers defect probability
2. makes the code easier to inspect and reason about
3. reduces control-flow complexity
4. uses data and routines clearly
5. protects the program against invalid states and misuse

Do not optimize for cleverness, minimal keystrokes, or fashionable idioms at the cost of clarity.

---

## Foundational Construction Rules

1. Write code primarily for human readers.
2. Favor clarity, locality, and explicitness over trickiness.
3. Keep control flow simple and visible.
4. Make correctness easier to achieve than incorrectness.
5. Use conventions consistently.

---

## Construction Prerequisites and Decisions

1. Do not treat construction as isolated typing; verify that requirements, architecture, major risks, and coding conventions are clear enough for the change.
2. Resolve major construction decisions before large implementation work: language constraints, error policy, data representation, reuse strategy, integration approach, and testing approach.
3. Use upstream uncertainty as a reason to build a small validated slice, not as an excuse for speculative code.
4. Keep the software metaphor or design model only if it helps make concrete construction decisions.
5. Measure twice before cutting when an early decision will be expensive to reverse.

---

## Pseudocode Programming Process

1. For complex routines, sketch the routine in precise pseudocode or comments before filling in details.
2. Refine pseudocode until it names the real steps at a consistent abstraction level.
3. Convert clear pseudocode into code and keep only comments that still add intent, constraints, or rationale.
4. Do not use pseudocode as a substitute for understanding the algorithm.

---

## Routine Design Rules

1. Routines should have one clear purpose.
2. The routine name should describe the result or action precisely.
3. Keep the interface as small as practical.
4. Avoid long parameter lists and flag arguments.
5. Separate setup, validation, computation, and side effects when they are conceptually different.
6. Return values should be meaningful and hard to misuse.
7. Prefer guard clauses and straightforward structure over deeply nested logic.

Anti-patterns (MUST NOT):
- routines that do several unrelated things
- routines whose names describe implementation detail instead of purpose
- many hidden side effects
- boolean parameters that switch routine mode

---

## Variable and Data Rules

1. Use names that reveal purpose and meaning.
2. Keep variable scope as small as practical.
3. Initialize variables deliberately.
4. Prefer named constants or stable values where a variable is not meant to change.
5. Avoid magic numbers and unexplained sentinel values.
6. Use stronger data types when primitives hide meaning.

Anti-patterns (MUST NOT):
- reused loop/index/temp variables beyond their purpose
- long-lived mutable locals carrying many meanings
- values whose units or semantics are unclear

---

## Data Type Rules

1. Choose data types that make invalid or ambiguous values harder to represent.
2. Name constants for magic values, units, bounds, and sentinel meanings.
3. Use booleans only for true binary meanings; replace flag fields with clearer states when needed.
4. Use enumerations or named alternatives when a value belongs to a closed set.
5. Use arrays, records, maps, and tables only where their shape communicates the data meaning.
6. Encapsulate unusual data structures behind routines or types that reveal purpose.
7. Keep units, ranges, precision, encoding, and ownership visible near the data they affect.

---

## Control Flow Rules

1. Prefer the simplest control flow that expresses the logic.
2. Keep nesting shallow when possible.
3. Replace complicated boolean logic with named predicates or clearer structure.
4. Use case/switch constructs only when they improve clarity.
5. Eliminate impossible paths and dead branches.
6. Avoid surprising exits unless they clarify the routine.

Anti-patterns (MUST NOT):
- deeply nested conditionals
- complicated loop exits with hidden state changes
- control flow dependent on side effects in expressions
- clever one-liners that obscure the logic

---

## Statement, Conditional, and Loop Rules

1. Organize straight-line code so dependencies appear before use and related statements stay together.
2. Keep conditionals positive and direct when possible.
3. Put the normal path where readers can find it quickly.
4. Use loops with clear initialization, termination, and update rules.
5. Keep loop bodies focused; extract work when a loop hides several responsibilities.
6. Avoid unusual control structures unless they are clearer than ordinary alternatives.
7. Use table-driven methods when repeated branching is stable and the table can be validated.

---

## Defensive Programming Rules

1. Validate inputs at trust boundaries.
2. Use assertions or invariant checks where programmer assumptions matter.
3. Distinguish between recoverable conditions and programming errors.
4. Fail in a way that preserves diagnosability.
5. Do not silently continue from corrupted or impossible state.

Anti-patterns (MUST NOT):
- assuming all callers are correct
- burying invalid state until it causes distant failures
- swallowing exceptions without context

---

## Error Handling Rules

1. Handle errors at the right level of abstraction.
2. Preserve useful context.
3. Do not let error handling dominate the normal path.
4. Standardize similar failure handling.
5. Prefer explicit, well-understood failure semantics over ad hoc conventions.

---

## Table-Driven and Data-Driven Rules

1. Prefer data-driven logic over long repeated condition chains when the mapping is stable and explicit.
2. Use tables or maps for configuration-like decisions.
3. Keep the structure obvious and validated.
4. Do not hide complex logic in inscrutable data encodings.

---

## Class and Module Design Rules

1. Each class or module should own a focused responsibility.
2. Separate interface from implementation.
3. Hide representation and incidental detail.
4. Keep classes cohesive.
5. Reduce coupling through clear contracts and limited knowledge of internals.

Anti-patterns (MUST NOT):
- god classes
- modules with mixed persistence, formatting, business logic, and integration concerns
- public surfaces that expose internal bookkeeping

---

## Complexity Management Rules

1. Treat rising complexity as a defect risk.
2. Prefer simple code over clever code.
3. Break apart large or tangled routines and modules.
4. Remove duplication that multiplies maintenance effort.
5. Choose designs that reduce the amount a maintainer must keep in working memory.

---

## Construction with Preconditions and Postconditions

1. Be explicit about routine assumptions.
2. Encode important invariants close to the code they protect.
3. Keep contracts simple and testable.
4. Use assertions for programmer mistakes, validation for external input, and domain errors for expected business failures.

---

## Comment Rules

1. Comments should explain intent, rationale, contracts, and non-obvious facts.
2. Do not comment obvious code instead of improving it.
3. Keep comments accurate or delete them.
4. Prefer self-documenting structure first, comments second.

---

## Coding Standards Rules

1. Be consistent within the codebase.
2. Use formatting, naming, and file structure to support readability.
3. Standardize common idioms so readers do not need to relearn style per module.
4. Prefer a shared convention over local personal taste.

---

## Incremental Construction Rules

1. Build in small, verifiable increments.
2. Integrate frequently enough to surface conflicts and misunderstanding early.
3. Keep partial work from rotting in long-lived isolation.
4. Review and improve code as part of construction, not only after it.

---

## Quality, Collaboration, Debugging, and Refactoring

1. Use reviews, inspections, pair work, tests, and static checks according to the risk of the code.
2. Treat debugging as diagnosis: reproduce, isolate, explain, fix, and verify rather than guessing.
3. Fix the root cause when practical, not only the symptom.
4. Add tests around defects so the same failure is easier to detect next time.
5. Refactor when structure hides intent, duplicates knowledge, or raises defect probability.
6. Keep refactoring separate from behavior changes when that improves reviewability.

---

## Performance, Integration, Tools, and Craftsmanship

1. Do not tune performance until the requirement and evidence justify it.
2. When tuning is justified, measure before and after, and keep clarity unless the tradeoff is explicit.
3. Integrate frequently enough to expose construction conflicts early.
4. Use programming tools, scripts, debuggers, profilers, editors, and build automation to reduce error-prone manual work.
5. Keep layout and style consistent enough that readers can focus on meaning.
6. Prefer self-documenting code, but add documentation where the code cannot express intent, constraints, or usage.
7. Treat personal discipline, curiosity, and ability to withstand careful review as part of construction quality.

---

## Review Rules

When reviewing code, actively look for:
- unclear names
- weak routine boundaries
- long parameter lists
- unnecessary nesting
- hidden side effects
- poor defensive checks at trust boundaries
- duplicated logic
- confusing control flow
- god classes or mixed responsibilities
- comments compensating for poor structure

---

## Forbidden Patterns

### Cleverness over Clarity
- dense tricks that are hard to inspect
- compressed expressions that save lines but increase interpretation cost

### Routine Bloat
- one routine doing several phases and concerns
- long signatures with many unrelated parameters

### Defensive Vacuum
- no validation at trust boundaries
- no checks around critical assumptions
- silent fallback from impossible state

### Comment-as-Crutch
- obvious comments over bad code
- stale comments that mislead

### Consistency Neglect
- arbitrary naming and formatting changes
- module-specific mini dialects inside one codebase

---

## Code Generation Rules

When generating code, default to:
1. clear names
2. focused routines
3. explicit data meaning
4. simple control flow
5. defensive checks at boundaries
6. cohesive classes/modules
7. consistent style

Avoid by default:
- dense clever code
- broad god objects
- fragile hidden assumptions
- unnecessary complexity in loops and conditionals
- comments where better names or decomposition would do

---

## Testing Rules

1. Test routine behavior around normal, boundary, and invalid inputs.
2. Test defensive checks where boundary validation matters.
3. Keep tests aligned with routine contracts.
4. Test complex data-driven logic with representative tables and edge cases.

---

## Review Checklist

Before finalizing any change, verify:
- Are names clear and intention-revealing?
- Are routines focused and reasonably small?
- Is control flow straightforward?
- Are trust boundaries defended?
- Are contracts and invariants explicit enough?
- Did we reduce or at least not increase complexity?
- Are classes/modules cohesive?
- Did we avoid cleverness that harms inspection?
- Are comments used only where they add value?
- Is the style consistent with the rest of the codebase?

If any answer is no, revise before shipping.

---

## Final Instruction

When uncertain, choose the option that:
1. lowers defect risk
2. improves readability
3. simplifies control flow
4. strengthens defensive correctness
5. keeps the code easier to inspect and maintain

Write code that would stand up to careful review.


# OBEY The Pragmatic Programmer by Andrew Hunt and David Thomas

## Purpose

This repository follows **The Pragmatic Programmer** in the sense of Andrew Hunt and David Thomas:
work pragmatically, take responsibility for quality, automate what is repetitive, and keep code and process adaptable.

All code generation, edits, and reviews must optimize for:
- clear ownership and responsibility
- avoiding duplicated knowledge
- orthogonality
- incremental delivery
- ruthless feedback
- automation of repetitive work
- code that is easy to change and easy to reason about

This file is a binding engineering policy: `MUST` is binding, `SHOULD` is a strong default, and `MUST NOT` is forbidden.

---

## Primary Directive

Be pragmatic, not dogmatic.

When uncertain, choose the option that:
1. reduces knowledge duplication
2. keeps concerns independent
3. shortens feedback loops
4. leaves the system easier to change
5. makes intent clearer to future maintainers

Do not follow style or process rituals that do not improve outcomes.

---

## Core Pragmatic Principles

### Own the Result
1. Take responsibility for the quality and changeability of the code you touch.
2. Do not blame tooling, framework defaults, or “existing style” for avoidable bad design.
3. Surface trade-offs, risks, and uncertainty explicitly.

### Think Beyond the Local Edit
1. Every change affects future maintainability.
2. Small quick fixes that multiply future cost are usually a bad bargain.
3. Leave the area better than you found it.

### Favor Adaptability
1. Build systems that are easy to observe, test, and change.
2. Prefer flexible boundaries over brittle cleverness.
3. Avoid premature commitment when requirements are still moving.

### Named Pragmatic Habits
1. Treat quality as a requirement to negotiate with users and sponsors, not as an abstract pursuit of perfection.
2. Stop polishing when the software is good enough for its real users and risks.
3. Keep a knowledge portfolio: invest in learning, diversify skills, and revisit stale assumptions.
4. Communicate decisions, risks, and tradeoffs clearly enough that others can act on them.
5. Watch for entropy and small broken windows before they become normal.
6. Use Stone Soup tactics only to create real progress, not to hide missing agreement.
7. Watch for boiled-frog drift where gradual degradation becomes invisible.

---

## DRY Rules

DRY means **do not duplicate knowledge**, not merely do not duplicate text.

1. A business rule should have one authoritative representation.
2. Validation logic for the same concept should not be scattered.
3. Status semantics, mappings, and calculations should not be copied across layers.
4. Configuration and schema meaning should not be repeated inconsistently.
5. Avoid duplicated process steps that can be automated.

Anti-patterns (MUST NOT):
- the same rule encoded in UI, API, service, and DB trigger with no ownership
- copy/paste with minor edits for “just this one case”
- duplicated manual deployment or testing steps
- one concept with multiple partially aligned implementations

---

## Orthogonality Rules

1. Keep components independent so one change does not force unrelated changes elsewhere.
2. Minimize hidden couplings through globals, ambient context, or shared mutable state.
3. Avoid overlapping responsibilities between modules.
4. Separate policy from mechanism, data from presentation, orchestration from computation.

Anti-patterns (MUST NOT):
- one change requiring edits in many unrelated places
- one module knowing too much about internal details of others
- shared utility modules creating sideways coupling everywhere

---

## Tracer Bullets and Iterative Delivery

1. Prefer a thin end-to-end slice over a pile of isolated pieces.
2. Use tracer bullets to validate architecture, integration, and assumptions early.
3. Keep the first slice simple but real enough to prove the path.
4. Refine from working feedback instead of predicting everything up front.

Anti-patterns (MUST NOT):
- building many layers before anything runs end to end
- treating prototypes as production without hardening
- waiting for perfect certainty before integrating

---

## Reversibility, Domain Languages, and Requirements

1. Preserve reversibility when requirements, vendors, platforms, databases, or deployment environments may change.
2. Avoid irreversible commitments until evidence makes them worth the cost.
3. Use a small domain language when it expresses domain rules more directly than general-purpose code.
4. Keep domain languages readable by the people who must validate or change them.
5. Dig for real requirements; do not accept current implementation details as requirements.
6. Do not fall into the specification trap where prose keeps growing but uncertainty does not fall.
7. Start building a working slice when further specification no longer reduces meaningful risk.
8. Respect informed hesitation: if the team is not ready, identify the missing information or feedback.

---

## Prototyping Rules

1. Use prototypes to learn, not to pretend you are done.
2. Be explicit about what a prototype proves and what it does not.
3. Do not let experimental shortcuts silently become production defaults.
4. Carry forward only the lessons or code that still deserve to survive.

---

## Automation Rules

1. Automate repetitive, error-prone, or easy-to-forget tasks.
2. Prefer repeatable scripts over tribal-knowledge commands.
3. Build, test, lint, format, package, and deploy steps should be reproducible.
4. Keep local automation aligned with the project's shared build, test, and release automation.

Anti-patterns (MUST NOT):
- “works on my machine” build steps
- manual release rituals with many hidden prerequisites
- documentation that describes what a script should do instead of having the script

---

## Feedback Loop Rules

1. Shorten the time between change and feedback.
2. Run relevant tests early and often.
3. Use automated checks where they reduce real risk.
4. Make failure visible fast.
5. Prefer a cheap early signal over a late expensive surprise.

---

## Design by Contract and Assertions

1. Make assumptions explicit in code.
2. Use assertions or invariant checks where they clarify impossible states.
3. Distinguish between programmer errors, contract violations, and expected domain failures.
4. Keep contracts close to the abstraction they protect.

Anti-patterns (MUST NOT):
- relying on comments for critical preconditions
- hiding invariant assumptions in scattered callers
- returning nonsense values for impossible states

---

## Error Handling and Recovery

1. Detect errors close to their source.
2. Do not discard useful error context.
3. Let callers distinguish retryable, recoverable, and permanent failures where relevant.
4. Fail loudly enough to diagnose, but with boundaries that prevent system-wide collapse.

---

## Naming and Communication Rules

1. Code is communication first.
2. Use names that reflect domain meaning and developer intent.
3. Prefer clarity over cleverness.
4. Write comments or docs where they convey decision rationale, contracts, or non-obvious behavior.
5. Writing is part of engineering, not overhead.

---

## Text and Data Rules

1. Favor plain text and open formats for long-lived automation and integration where practical.
2. Make scripts and configs inspectable and diffable.
3. Keep serialization and config formats explicit and version-aware.
4. Avoid opaque binary or framework-specific lock-in unless justified.

---

## State and Concurrency Rules

1. Treat shared mutable state as expensive.
2. Prefer immutability, isolation, or explicit synchronization when state is shared.
3. Keep concurrency assumptions visible.
4. Do not add asynchronous complexity unless it clearly earns its cost.

---

## Estimation and Increment Rules

1. Break work into pieces that can be reasoned about, tested, and corrected.
2. Keep plans and estimates honest about uncertainty.
3. Prefer small deliverable increments to large hidden progress.
4. Make risk visible early.

---

## Tooling Rules

1. Know and use the tools that amplify correctness and speed.
2. Do not hand-do tasks that should be scripted.
3. Keep editor, formatter, lint, tests, and local scripts aligned with team standards.
4. Improve the toolchain when repeated friction appears.

### Basic Tool Rules
- Use source control for every meaningful project, including small or solo work.
- Prefer inspectable plain text for long-lived scripts, configs, data, and generated sources when practical.
- Use shell tools for exploration, automation, and repeatable transformations where they fit.
- Use editor capabilities to reduce repetitive manual edits.
- Use text manipulation languages or scripts for systematic changes that would be error-prone by hand.
- Use code generators to remove duplicated mechanical work, but keep the source specification authoritative.
- When debugging, do not guess: reproduce, observe, isolate, explain, fix, and verify.
- Do not rely on generated code, tools, specifications, or formal methods you do not understand.

---

## Resource and Coupling Rules

1. Finish what you start when allocating, opening, locking, or otherwise acquiring resources.
2. Release every resource you acquire, preferably in the opposite order from acquisition.
3. Keep resource ownership local and explicit.
4. Apply shy-code and Law of Demeter discipline so modules reveal only necessary information.
5. Avoid temporal coupling; make ordering requirements explicit or remove them.
6. Use metaprogramming only when it reduces duplication or improves adaptability without hiding behavior.
7. Use blackboard-style coordination only when uncertain order, multiple sources, or opportunistic collaboration justify it.
8. Understand algorithmic growth before writing or accepting performance-sensitive code.

---

## Project and Team Rules

1. Build pragmatic teams around shared responsibility, automation, fast feedback, and visible quality.
2. Test unit behavior, integration, validation and verification, resource exhaustion, errors and recovery, performance, usability, and tests themselves where relevant.
3. Treat writing as engineering work: docs, comments, commit messages, scripts, and tests must communicate intent.
4. Set expectations explicitly with users and stakeholders.
5. Take pride in code, tests, documentation, and generated artifacts.
6. Be skeptical of methods, diagrams, and ceremonies that do not improve the work.

---

## Broken Windows Rule

1. Do not normalize local decay.
2. Fix small quality problems before they signal that nobody cares.
3. Tidy the code you touch where the cost is low and the value is immediate.
4. Avoid leaving behind “temporary” hacks with no cleanup plan.

---

## Review Rules

When reviewing code, actively look for:
- duplicated knowledge, not just duplicated lines
- hidden couplings
- missing automation opportunities
- long feedback loops
- local fixes that worsen future changeability
- unclear contracts or assumptions
- non-repeatable manual processes
- brittle integration points
- code that communicates poorly

---

## Forbidden Patterns

### Cargo-Cult Process
- rituals followed with no benefit
- documentation and checklists replacing automation

### Knowledge Duplication
- same rule in many places
- copied logic because “layers need it too”

### Non-Orthogonal Design
- modules with overlapping responsibilities
- changes leaking across boundaries by default

### Manual Everything
- repeated human steps for build, test, release, setup, or validation
- hidden local environment assumptions

### Prototype Fossilization
- experimental code promoted to production without redesign or hardening

---

## Code Generation Rules

When generating code, default to:
1. one clear source of truth for each rule
2. orthogonal responsibilities
3. fast local feedback
4. automation over repeated manual work
5. explicit contracts and assumptions
6. readable names and communication
7. incremental end-to-end slices when building new capabilities

Avoid by default:
- copy/paste rule duplication
- tangled modules
- fragile manual workflows
- overcommitting to an architecture before the first end-to-end path works

---

## Testing Rules

1. Keep tests runnable quickly and often.
2. Prefer tests that align with the business or technical contract being protected.
3. Use automation so validation is habitual, not heroic.
4. Keep flaky or environment-dependent tests out of the critical feedback path where possible.

---

## Review Checklist

Before finalizing any change, verify:
- Did we reduce duplicated knowledge?
- Are responsibilities more orthogonal after the change?
- Did we improve or preserve fast feedback?
- Did we automate anything repetitive that was hurting reliability?
- Are contracts and assumptions clearer?
- Is the code easier to communicate about?
- Did we avoid prototype shortcuts becoming silent production defaults?
- Did we fix at least one small “broken window” if it was in the touched area?

If any answer is no, revise before shipping.

---

## Final Instruction

When uncertain, choose the option that:
1. removes duplicated knowledge
2. keeps concerns orthogonal
3. shortens feedback loops
4. improves automation
5. leaves the codebase easier to change tomorrow

Be pragmatic, and make the right thing the easy thing.

---

# OBEY Clean Code by Robert C. Martin