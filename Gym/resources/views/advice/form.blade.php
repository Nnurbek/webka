@extends('layouts.user-layout')
@section('content')
<div class="content-wrapper pb-4">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 15px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .results {
            margin-top: 20px;
        }
        .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            margin-left: 5px;
            background-color: black;
            border-radius: 50%;
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0; }
            100% { opacity: 1; }
        }
    </style>

    <div class="container">
        <h1>Get Fitness Advice</h1>
        <form id="advice-form">
            @csrf
            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" id="age" name="age" required>
            </div>
            <div class="form-group">
                <label for="weight">Weight (kg)</label>
                <input type="number" id="weight" name="weight" required>
            </div>
            <div class="form-group">
                <label for="height">Height (cm)</label>
                <input type="number" id="height" name="height" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div class="form-group">
                <label for="fitness_level">Fitness Level</label>
                <select id="fitness_level" name="fitness_level" required>
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                </select>
            </div>
            <div class="form-group">
                <label for="goal">Goal</label>
                <select id="goal" name="goal" required>
                    <option value="weight_loss">Weight Loss</option>
                    <option value="muscle_gain">Muscle Gain</option>
                    <option value="maintain_fitness">Maintain Fitness</option>
                </select>
            </div>
            <button type="submit">Get Advice</button>
        </form>
        <div class="results" id="results"></div>
        <div class="dot" id="dot" style="display: none;"></div>
    </div>

    <script>
        document.getElementById('advice-form').addEventListener('submit', function(event) {
            event.preventDefault();

            const age = document.getElementById('age').value;
            const weight = document.getElementById('weight').value;
            const height = document.getElementById('height').value;
            const gender = document.getElementById('gender').value;
            const fitness_level = document.getElementById('fitness_level').value;
            const goal = document.getElementById('goal').value;

            const dot = document.getElementById('dot');
            dot.style.display = 'inline-block';

            fetch('/advice', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({
                    age: age,
                    weight: weight,
                    height: height,
                    gender: gender,
                    fitness_level: fitness_level,
                    goal: goal
                })
            })
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('results');
                if (data.error) {
                    resultsDiv.innerHTML = `<p>Error: ${data.error}</p>`;
                } else {
                    const workoutPlan = JSON.parse(data.workout_plan.replace(/'/g, '"'));
                    let workoutHtml = '<h3>Workout Plan:</h3>';
                    for (const [day, plan] of Object.entries(workoutPlan)) {
                        workoutHtml += `<div class="day-plan"><strong>${day.charAt(0).toUpperCase() + day.slice(1)}:</strong> ${plan}</div>`;
                    }

                    const nutritionPlan = JSON.parse(data.nutrition_plan.replace(/'/g, '"'));
                    let nutritionHtml = '<br><h3>Nutrition Plan:</h3>';
                    nutritionHtml += `<div><strong>Breakfast:</strong> ${nutritionPlan.breakfast}</div>`;
                    nutritionHtml += `<div><strong>Lunch:</strong> ${nutritionPlan.lunch}</div>`;
                    nutritionHtml += `<div><strong>Dinner:</strong> ${nutritionPlan.dinner}</div>`;
                    nutritionHtml += `<div><strong>Snacks:</strong> ${nutritionPlan.snacks.join(', ')}</div>`;
                    nutritionHtml += '<br><h4>Weekly Plan:</h4>';
                    for (const day of ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']) {
                        nutritionHtml += `<div class="day-plan"><strong>${day.charAt(0).toUpperCase() + day.slice(1)}:</strong> ${nutritionPlan[day]}</div>`;
                    }

                    const finalHtml = `
                        <h2>Advice</h2>
                        <div class="workout-plan">${workoutHtml}</div>
                        <div class="nutrition-plan">${nutritionHtml}</div>
                        <div class="health-tips"><br><h3>Health Tips:</h3> ${data.health_tips.join(', ')}</div>
                    `;

                    displayText(resultsDiv, finalHtml, dot);
                }
            })
            .catch(error => console.error('Error:', error));
        });

        function displayText(element, html, dot) {
            element.innerHTML = '';
            let tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            let nodes = Array.from(tempDiv.childNodes);
            let i = 0;
            function typeWriter() {
                if (i < nodes.length) {
                    let node = nodes[i];
                    element.appendChild(node);
                    i++;
                    setTimeout(typeWriter, 100); // Скорость печати
                } else {
                    dot.style.display = 'none';
                }
            }
            typeWriter();
        }
    </script>
</div>
@endsection
