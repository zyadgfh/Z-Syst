import { Injectable, Logger } from '@nestjs/common';
import axios from 'axios';
// eslint-disable-next-line @typescript-eslint/no-require-imports
const FormData = require('form-data');
import { readFileSync } from 'fs';

@Injectable()
export class AiService {
  private readonly logger = new Logger(AiService.name);

  /**
   * Sends an image buffer to the OCR service and returns extracted text.
   * Expects OCR_SERVICE_URL env variable like http://ocr-service:8080/ocr
   */
  async performOcr(imagePath: string): Promise<string> {
    const ocrUrl = process.env.OCR_SERVICE_URL;
    if (!ocrUrl) {
      this.logger.error('OCR_SERVICE_URL not set');
      throw new Error('OCR service URL not configured');
    }
    const form = new FormData();
    form.append('file', readFileSync(imagePath));
    const response = await axios.post(ocrUrl, form, {
      headers: form.getHeaders(),
    });
    return response.data?.text || '';
  }

  // Placeholder for future forecasting models
  async forecastDemand(data: any): Promise<any> {
    // TODO: integrate forecasting model
    return {};
  }
}
