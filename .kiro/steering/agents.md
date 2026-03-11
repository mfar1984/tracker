# Agent Instructions

## Server Management Rules

**CRITICAL - DO NOT RUN THESE COMMANDS:**
- `php artisan serve` - Server is already running by user
- `php artisan migrate:fresh` - Will destroy existing data
- Any server startup commands

## What TO DO:
- Test APIs using curl/http requests only
- Assume server is running on localhost:8000
- Use existing database data
- Follow user instructions exactly

## What NOT TO DO:
- Don't start servers
- Don't reset databases
- Don't ignore user instructions
- Don't be stubborn about commands