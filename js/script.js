document.addEventListener('DOMContentLoaded', function() {
    // Feeding Calculator functionality
    var calculatorForm = document.getElementById('calculatorForm');
    if (calculatorForm) {
        calculatorForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var weightInput = document.getElementById('weight');
            var weight = parseFloat(weightInput.value);

            if (isNaN(weight) || weight <= 0) {
                alert("Please enter a valid weight.");
                return;
            }

            // Calculation logic: total daily grams = weight (kg) * 50
            var total = weight * 50;
            var muscleMeat = total * 0.80;
            var bone = total * 0.10;
            var liver = total * 0.05;
            var otherOrgans = total * 0.05;

            // Update the result fields
            document.getElementById('totalAmount').innerText = total.toFixed(2);
            document.getElementById('muscleMeat').innerText = muscleMeat.toFixed(2);
            document.getElementById('bone').innerText = bone.toFixed(2);
            document.getElementById('liver').innerText = liver.toFixed(2);
            document.getElementById('otherOrgans').innerText = otherOrgans.toFixed(2);

            document.getElementById('result').style.display = 'block';
        });
    }
});