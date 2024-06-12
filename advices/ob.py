import json
import numpy as np
import pandas as pd
import tensorflow as tf
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler, OneHotEncoder
from sklearn.compose import ColumnTransformer
from sklearn.pipeline import Pipeline
from tensorflow.keras.models import Model
from tensorflow.keras.layers import Input, Dense, Dropout
import joblib

# Загрузка данных
df = pd.read_csv('augmented_ideal_fitness_dataset.csv')

# Определение признаков и меток
features = ['age', 'weight', 'height', 'gender', 'fitness_level', 'goal']
X = df[features]
y_workout = df['workout_plan'].apply(lambda x: str(x))
y_nutrition = df['nutrition_plan'].apply(lambda x: str(x))
y_health_tips = df['health_tips'].apply(lambda x: ', '.join(x))

# Предобработка данных
numeric_features = ['age', 'weight', 'height']
numeric_transformer = Pipeline(steps=[
    ('scaler', StandardScaler())
])

categorical_features = ['gender', 'fitness_level', 'goal']
categorical_transformer = Pipeline(steps=[
    ('onehot', OneHotEncoder(handle_unknown='ignore'))
])

preprocessor = ColumnTransformer(
    transformers=[
        ('num', numeric_transformer, numeric_features),
        ('cat', categorical_transformer, categorical_features)
    ])

# Обучение предобработчика на данных
X = preprocessor.fit_transform(X)

# Сохранение предобработчика после обучения
joblib.dump(preprocessor, 'preprocessor.pkl')

# Кодирование меток
y_workout = pd.get_dummies(y_workout)
y_nutrition = pd.get_dummies(y_nutrition)
y_health_tips = pd.get_dummies(y_health_tips)

# Сохранение кодировщиков меток
pd.DataFrame(columns=y_workout.columns).to_csv('y_workout_columns.csv', index=False)
pd.DataFrame(columns=y_nutrition.columns).to_csv('y_nutrition_columns.csv', index=False)
pd.DataFrame(columns=y_health_tips.columns).to_csv('y_health_tips_columns.csv', index=False)

# Разделение данных на обучающую и тестовую выборки
X_train, X_test, y_workout_train, y_workout_test = train_test_split(X, y_workout, test_size=0.2, random_state=42)
_, _, y_nutrition_train, y_nutrition_test = train_test_split(X, y_nutrition, test_size=0.2, random_state=42)
_, _, y_health_tips_train, y_health_tips_test = train_test_split(X, y_health_tips, test_size=0.2, random_state=42)

# Создание модели
input_layer = Input(shape=(X_train.shape[1],))
common_layer = Dense(128, activation='relu')(input_layer)
common_layer = Dropout(0.3)(common_layer)
common_layer = Dense(64, activation='relu')(common_layer)
common_layer = Dropout(0.3)(common_layer)

# Output layers for multitasking
workout_output = Dense(y_workout_train.shape[1], activation='softmax', name='workout_output')(common_layer)
nutrition_output = Dense(y_nutrition_train.shape[1], activation='softmax', name='nutrition_output')(common_layer)
health_tips_output = Dense(y_health_tips_train.shape[1], activation='sigmoid', name='health_tips_output')(common_layer)

model = Model(inputs=input_layer, outputs=[workout_output, nutrition_output, health_tips_output])
model.compile(optimizer='adam', 
              loss={'workout_output': 'categorical_crossentropy', 
                    'nutrition_output': 'categorical_crossentropy', 
                    'health_tips_output': 'binary_crossentropy'},
              metrics={'workout_output': 'accuracy', 
                       'nutrition_output': 'accuracy', 
                       'health_tips_output': 'accuracy'})

# Обучение модели
history = model.fit(X_train, {'workout_output': y_workout_train, 
                              'nutrition_output': y_nutrition_train, 
                              'health_tips_output': y_health_tips_train},
                    epochs=50, batch_size=16, validation_split=0.2)

# Оценка модели
results = model.evaluate(X_test, 
                         {'workout_output': y_workout_test, 
                          'nutrition_output': y_nutrition_test, 
                          'health_tips_output': y_health_tips_test})

# Вывод всех значений результатов для диагностики
print("Results from model.evaluate:", results)

# Извлечение значений потерь и точности, если они присутствуют
if len(results) == 4:
    total_loss, health_tips_accuracy, nutrition_accuracy, workout_accuracy = results
    print(f'Total Loss: {total_loss}')
    print(f'Workout Plan - Test Accuracy: {workout_accuracy}')
    print(f'Nutrition Plan - Test Accuracy: {nutrition_accuracy}')
    print(f'Health Tips - Test Accuracy: {health_tips_accuracy}')
else:
    print("Unexpected number of results returned. Please check the results output for debugging.")

# Сохранение модели в формате Keras
model.save('fitness_model.keras')

# Сохранение истории обучения
history_df = pd.DataFrame(history.history)
history_df.to_csv('training_history.csv', index=False)

# Сохранение итоговых метрик
results_dict = {
    'total_loss': results[0],
    'health_tips_accuracy': results[1],
    'nutrition_accuracy': results[2],
    'workout_accuracy': results[3]
}
with open('evaluation_results.json', 'w') as f:
    json.dump(results_dict, f)
