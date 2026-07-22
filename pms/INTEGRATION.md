# OCR and Forecasting Integration Notes

This file describes how to integrate OCR (prescription reader) and demand-forecasting models into the prototype.

1) OCR (Prescription Reader)
- Provide an endpoint `/api/ocr/upload` that accepts an image (multipart/form-data). Use `multer` to receive the file.
- Option A (cloud): send the image to a managed OCR/Document AI like AWS Textract, Google Vision OCR, or Azure Form Recognizer. Parse results, run an NLP normalization step to extract medicine names, quantities, and directions.
- Option B (on-prem/ML): host a lightweight inference service that runs a vision model (e.g., a fine-tuned Tesseract pipeline + CNN for handwriting segmentation or a transformer-based OCR). Use a microservice (FastAPI/Flask) with GPU if needed.
- After extraction, call internal product search to match recognized medicine names to catalog SKUs (fuzzy match on `activeIngredient` and `name`). Return a pre-filled cart to the POS frontend.

2) Demand Forecasting
- Build a dedicated forecasting microservice that consumes historical `Sale` data (aggregated by product and store) and runs a time-series model (Prophet, ARIMA, or LSTM).
- Provide an endpoint `/api/forecast/:productId?horizon=30` that returns expected demand for the horizon. For prototype, a simple linear/exponential smoothing fallback is acceptable.
- Use forecasts inside inventory auto-PO logic: suggestedQty = max(0, forecasted_demand_for_next_14_days + safety_stock - current_qty).

3) Deployment and ML orchestration
- Package models as containers and expose HTTP/gRPC endpoints.
- Use a feature store (Redis/Postgres) for inputs and predictions caching.
- Schedule retraining and monitoring pipelines (Airflow, Prefect, or GitHub Actions).

4) Security & Privacy
- Ensure OCR images and PHI are transmitted over TLS and stored encrypted at rest.
