# Taste

- When analysis identifies gaps or missing pieces, expects the agent to follow through and implement the fixes rather than only report them (e.g. "do what is not present as you analysis"). Confidence: 0.7
- Excludes the internal `.commandcode/` directory from git staging and commits. Confidence: 0.8
- Writes descriptive git commit messages with a summary line, bullet-point body, and a `Co-authored-by` trailer. Confidence: 0.7
- Prefers the agent to operate on the production server (20i StackCP shared hosting) directly over SSH — running migrations and deploying itself — rather than only handing back commands for the user to run manually. Confidence: 0.7
