from flask import Flask, request, jsonify
import tensorflow as tf
import numpy as np
import pandas as pd
import joblib

app = Flask(__name__)

# Загрузка модели
model = tf.keras.models.load_model('fitness_model.keras')

# Загрузка предобработчика
preprocessor = joblib.load('preprocessor.pkl')

# Список признаков
features = ['age', 'weight', 'height', 'gender', 'fitness_level', 'goal']

# Загрузка кодировщиков меток
y_workout = pd.read_csv('y_workout_columns.csv').columns.tolist()
y_nutrition = pd.read_csv('y_nutrition_columns.csv').columns.tolist()
y_health_tips = pd.read_csv('y_health_tips_columns.csv').columns.tolist()

@app.route('/get_advice', methods=['GET'])
def get_advice():
    # Получение данных из запроса
    age = request.args.get('age')
    weight = request.args.get('weight')
    height = request.args.get('height')
    gender = request.args.get('gender')
    fitness_level = request.args.get('fitness_level')
    goal = request.args.get('goal')

    # Проверка наличия всех необходимых данных
    if not all([age, weight, height, gender, fitness_level, goal]):
        return jsonify({'error': 'Missing data'}), 400

    # Преобразование данных в нужные типы
    age = int(age)
    weight = float(weight)
    height = float(height)

    # Создание DataFrame для данных
    input_df = pd.DataFrame([[age, weight, height, gender, fitness_level, goal]], columns=features)
    
    # Предобработка данных
    X = preprocessor.transform(input_df)
    
    # Прогнозирование
    predictions = model.predict(X)
    
    workout_pred = np.argmax(predictions[0], axis=1)
    nutrition_pred = np.argmax(predictions[1], axis=1)
    health_tips_pred = (predictions[2] > 0.5).astype(int)
    
    # Преобразование предсказаний в текст
    workout_plan = y_workout[workout_pred[0]]
    nutrition_plan = y_nutrition[nutrition_pred[0]]
    health_tips = [y_health_tips[i] for i in range(len(y_health_tips)) if health_tips_pred[0][i] == 1]
    
    response = {
        'workout_plan': workout_plan,
        'nutrition_plan': nutrition_plan,
        'health_tips': health_tips
    }
    
    return jsonify(response)

if __name__ == '__main__':
    app.run(debug=True, port=5001)  # Измените порт здесь
