from flask import Blueprint, request, jsonify
from app.services.gemini_service import (
    generate_path,
    chat,
    generate_lesson_content,
    generate_quiz,
    get_adaptive_recommendation,
    get_progress_insights,
)

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
        return jsonify({"error": "topic is required"}), 400

    content = generate_lesson_content(topic, description, user_goal)
    return jsonify({"content": content})


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

    try:
        content = generate_lesson_content(topic, description, user_goal, feedback)
        return jsonify({"content": content})
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@agent_bp.route('/paths/<path_id>', methods=['DELETE'])
def delete_path_route(path_id):
    # Persistence is handled client-side via localStorage
    return jsonify({"message": "Path deleted successfully"})


@agent_bp.route('/generate-quiz', methods=['POST'])
def generate_quiz_route():
    data = request.json
    if not data:
        return jsonify({"error": "No data provided"}), 400

    topic = data.get('topic')
    lesson_content = data.get('lessonContent', '')
    user_goal = data.get('userGoal', '')

    if not topic:
        return jsonify({"error": "topic is required"}), 400

    result = generate_quiz(topic, lesson_content, user_goal)
    return jsonify(result)


@agent_bp.route('/adaptive-recommendation', methods=['POST'])
def adaptive_recommendation_route():
    data = request.json
    if not data:
        return jsonify({"error": "No data provided"}), 400

    topic = data.get('topic')
    score = data.get('score')
    total = data.get('total')

    if topic is None or score is None or total is None:
        return jsonify({"error": "topic, score, and total are required"}), 400

    result = get_adaptive_recommendation(topic, score, total)
    return jsonify(result)


@agent_bp.route('/progress-insights', methods=['POST'])
def progress_insights_route():
    data = request.json
    if not data or 'classData' not in data:
        return jsonify({"error": "classData is required"}), 400

    insights = get_progress_insights(data['classData'])
    return jsonify({"insights": insights})
