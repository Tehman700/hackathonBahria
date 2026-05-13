from flask import Blueprint, request, jsonify
from app.services.gemini_service import generate_path, chat

agent_bp = Blueprint('agent', __name__)

@agent_bp.route('/generate-path', methods=['POST'])
def generate_path_route():
    if request.is_json:
        data = request.json
        files = None
    else:
        data = request.form
        files = request.files.getlist('files')

    if not data:
        return jsonify({"error": "No data provided"}), 400

    result = generate_path(data, files)
    return jsonify(result)

@agent_bp.route('/chat', methods=['POST'])
def chat_route():
    data = request.json
    if not data or 'message' not in data:
        return jsonify({"error": "Message is required"}), 400

    history = data.get('history', [])
    message = data.get('message')
    context = data.get('context', {})

    response_text = chat(history, message, context)
    return jsonify({"role": "model", "text": response_text})

@agent_bp.route('/generate-lesson', methods=['POST'])
def generate_lesson_route():
    data = request.json
    if not data:
        return jsonify({"error": "No data provided"}), 400

    topic = data.get('topic')
    description = data.get('description')
    user_goal = data.get('userGoal')

    if not topic:
        return jsonify({"error": "Topic is required"}), 400

    from app.services.gemini_service import generate_lesson_content
    content = generate_lesson_content(topic, description, user_goal)
    return jsonify({"content": content})

@agent_bp.route('/paths/<path_id>', methods=['DELETE'])
def delete_path_route(path_id):
    # Persistence is handled client-side via localStorage.
    # This endpoint is a no-op when Firebase is not configured.
    return jsonify({"message": "Path deleted successfully"})

@agent_bp.route('/modules/<module_id>/regenerate', methods=['POST'])
def regenerate_lesson_endpoint(module_id):
    data = request.json
    if not data:
        return jsonify({"error": "No data provided"}), 400

    topic = data.get('topic')
    description = data.get('description')
    user_goal = data.get('userGoal')
    feedback = data.get('feedback', [])

    if not topic:
        return jsonify({"error": "topic is required"}), 400

    from app.services.gemini_service import generate_lesson_content
    try:
        content = generate_lesson_content(topic, description, user_goal, feedback)
        return jsonify({"content": content})
    except Exception as e:
        print(f"Error regenerating lesson: {e}")
        return jsonify({"error": str(e)}), 500
