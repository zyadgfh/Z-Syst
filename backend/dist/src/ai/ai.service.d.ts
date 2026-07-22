export declare class AiService {
    private readonly logger;
    performOcr(imagePath: string): Promise<string>;
    forecastDemand(data: any): Promise<any>;
}
