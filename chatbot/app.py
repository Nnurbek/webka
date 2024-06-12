import pandas as pd
import nltk
from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.preprocessing import LabelEncoder
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import Dense, Dropout
from tensorflow.keras.optimizers import Adam
import joblib
from flask import Flask, request, jsonify
import numpy as np
from tensorflow.keras.models import load_model

nltk.download('punkt')

# Загрузка данных
data = pd.read_csv('fitness_chatbot_dataset.csv')

# Разделение данных на обучающую и тестовую выборки
X_train, X_test, y_train, y_test = train_test_split(data['question'], data['answer'], test_size=0.2, random_state=42)

# Векторизация текстовых данных
vectorizer = TfidfVectorizer()
X_train_tfidf = vectorizer.fit_transform(X_train)
X_test_tfidf = vectorizer.transform(X_test)

# Кодирование меток
label_encoder = LabelEncoder()
y_train_encoded = label_encoder.fit_transform(y_train)
y_test_encoded = label_encoder.transform(y_test)

# Создание модели нейронной сети
model = Sequential()
model.add(Dense(512, input_dim=X_train_tfidf.shape[1], activation='relu'))
model.add(Dropout(0.5))
model.add(Dense(256, activation='relu'))
model.add(Dropout(0.5))
model.add(Dense(len(label_encoder.classes_), activation='softmax'))

model.compile(optimizer=Adam(learning_rate=0.001), loss='sparse_categorical_crossentropy', metrics=['accuracy'])

# Обучение модели
model.fit(X_train_tfidf.toarray(), y_train_encoded, epochs=10, batch_size=32, validation_data=(X_test_tfidf.toarray(), y_test_encoded))

# Сохранение модели и векторизатора
model.save('deep_learning_model.h5')
joblib.dump(vectorizer, 'vectorizer.pkl')
joblib.dump(label_encoder, 'label_encoder.pkl')

# Flask приложение
app = Flask(__name__)

# Загрузка модели и других необходимых объектов
model = load_model('deep_learning_model.h5')
vectorizer = joblib.load('vectorizer.pkl')
label_encoder = joblib.load('label_encoder.pkl')

def get_answer_with_confidence(question, model, vectorizer, label_encoder, threshold=0.5, default_response="I am not trained to answer this question yet"):
    vectorized_question = vectorizer.transform([question])
    probabilities = model.predict(vectorized_question.toarray())
    max_proba = np.max(probabilities)
    if max_proba < threshold:
        return default_response
    predicted_label = np.argmax(probabilities)
    return label_encoder.inverse_transform([predicted_label])[0]

@app.route('/get_answer', methods=['GET'])
def get_answer():
    question = request.args.get('question')
    if question:
        answer = get_answer_with_confidence(question, model, vectorizer, label_encoder)
        return jsonify({"answer": answer})
    return jsonify({"error": "No question provided"})

if __name__ == '__main__':
    app.run(debug=True)
