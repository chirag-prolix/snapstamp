The user has just finished teaching you a workflow. Review what happened in this conversation and turn it into a permanent Claude Code slash command.

$ARGUMENTS

Steps:

1. Look back at the full conversation. Identify the workflow that was taught or demonstrated — the sequence of steps, the decisions made, the files touched, the commands run.

2. Synthesize it into a slash command definition. The command should:
   - Start with a one-sentence description of what the workflow does and when to use it
   - Include `$ARGUMENTS` if the workflow takes an input (e.g. a name, an ID, a feature description)
   - List each step as a concrete instruction Claude should follow when the command is invoked
   - Include any conditions, edge cases, or non-obvious decisions that were mentioned
   - End with what "done" looks like

3. Choose a short verb-noun command name based on what was taught (e.g. `add-endpoint`, `run-migration`). If the user already named it during /identify-workflow or /teach, use that name.

4. Write the command file to `.claude/commands/<name>.md` using the Write tool.

5. Confirm what was saved:
   ```
   Saved: .claude/commands/<name>.md
   Invoke it with: /<name>
   ```

If the conversation does not contain a clear workflow to capture, say so and ask the user to run /teach first and walk you through the workflow live.
