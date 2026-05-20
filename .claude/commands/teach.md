The user is about to walk you through a workflow they want you to learn. Your job is to observe, follow along, and record.

$ARGUMENTS

Enter observation mode:
- Pay close attention to every step the user takes, every command they run, every file they touch, and every decision they make.
- After each step, confirm you understood it: restate what was just done in one sentence.
- If a step is ambiguous, ask one short clarifying question before moving on.
- If the user skips something or you notice a gap, flag it: "I noticed we went from X to Z — did we skip a step, or is that intentional?"
- Track the full sequence internally as a numbered list.

When the user signals they are done (e.g. "done", "that's it", "finished"), print a full recap:

```
Workflow recap:
1. <step>
2. <step>
...

Key decisions made:
- <any non-obvious choices or conditions>

Edge cases to handle:
- <anything that could go differently>
```

Then say: "Run /review to write this as a permanent slash command."
