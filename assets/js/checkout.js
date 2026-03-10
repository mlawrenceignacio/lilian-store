document.addEventListener("DOMContentLoaded", () => {
  const paymentRadios = document.querySelectorAll(
    'input[name="payment_method"]',
  );
  const paymentProofWrap = document.getElementById("paymentProofWrap");
  const paymentProofInput = document.getElementById("payment_proof");
  const selectedFileName = document.getElementById("selectedFileName");

  const togglePaymentProof = () => {
    const selected = document.querySelector(
      'input[name="payment_method"]:checked',
    );
    if (!selected || !paymentProofWrap || !paymentProofInput) return;

    const needsProof =
      selected.value === "GCash" || selected.value === "GoTyme";

    paymentProofWrap.classList.toggle("hidden", !needsProof);
    paymentProofInput.required = needsProof;

    if (!needsProof) {
      paymentProofInput.value = "";
      if (selectedFileName) {
        selectedFileName.textContent = "No file selected yet.";
      }
    }
  };

  paymentRadios.forEach((radio) => {
    radio.addEventListener("change", togglePaymentProof);
  });

  if (paymentProofInput && selectedFileName) {
    paymentProofInput.addEventListener("change", () => {
      if (paymentProofInput.files && paymentProofInput.files.length > 0) {
        selectedFileName.textContent = `Selected file: ${paymentProofInput.files[0].name}`;
      } else {
        selectedFileName.textContent = "No file selected yet.";
      }
    });
  }

  togglePaymentProof();
});
