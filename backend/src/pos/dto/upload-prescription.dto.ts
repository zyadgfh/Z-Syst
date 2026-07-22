import { IsInt, IsString } from 'class-validator';

export class UploadPrescriptionDto {
  @IsInt()
  patientId: number;

  @IsString()
  filePath: string; // Path to the uploaded image file
}
