The user wants to capture a repeatable workflow as a reusable Claude Code skill.

$ARGUMENTS

Run a short structured interview — ask these questions one at a time, wait for answers, then move to the next:

1. What task do you find yourself repeating? Describe it in one sentence.
2. What triggers it? (e.g. "a new feature request", "a bug report", "before every deploy")
3. Walk me through the steps in order — be specific about which files you touch, what commands you run, what you check.
4. What does "done" look like? What's the output or end state?
5. What should the slash command be called? (short, verb-noun style — e.g. `add-endpoint`, `run-migration`, `deploy-staging`)

After all answers, print a structured summary:

```
Command: /<name>
Trigger: <when this runs>
Steps:
  1. ...
  2. ...
Output: <what done looks like>
```

Then say: "Ready to watch you do it for real. Run /teach and walk me through a live example — I'll learn from what you do. When we finish, run /review to save it as a permanent command."
