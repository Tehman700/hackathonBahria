# CogniPath AI Modules (Integrated into Moodle LMS)

This directory now keeps only the CogniPath backend code that is integrated into Moodle LMS features.

## What is kept
- `server/` Flask AI API used by Moodle plugins/blocks

## What was removed
- Standalone CogniPath frontend/UI
- Firebase/Cloud deployment scripts and configs
- Unused Firebase test/setup files

## Integrated API endpoints
- `POST /api/generate-path`
- `POST /api/chat`
- `POST /api/generate-lesson`
- `POST /api/modules/<module_id>/regenerate`
- `POST /api/generate-quiz`
- `POST /api/adaptive-recommendation`
- `POST /api/progress-insights`
- `DELETE /api/paths/<path_id>`

For setup and run steps, use the repository root `README.md`.
