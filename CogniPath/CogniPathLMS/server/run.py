from flask import Flask
from flask_cors import CORS
from app.routes.agent_routes import agent_bp
import os

app = Flask(__name__)

# Only Moodle LMS app origins for integrated usage.
CORS(app, origins=["http://localhost:8090", "http://127.0.0.1:8090"])

app.register_blueprint(agent_bp, url_prefix='/api')

if __name__ == '__main__':
    port = int(os.environ.get("PORT", 8080))
    app.run(debug=False, host='0.0.0.0', port=port)
