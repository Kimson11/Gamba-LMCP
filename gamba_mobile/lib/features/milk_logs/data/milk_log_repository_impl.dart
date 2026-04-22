import '../../../core/network/api_client.dart';
import '../../../core/network/idempotency_key_provider.dart';

class MilkLogRepositoryImpl {
  MilkLogRepositoryImpl({
    required this.apiClient,
    IdempotencyKeyProvider? idempotencyKeyProvider,
  }) : _idempotencyKeyProvider = idempotencyKeyProvider ?? IdempotencyKeyProvider();

  final ApiClient apiClient;
  final IdempotencyKeyProvider _idempotencyKeyProvider;

  Future<void> create({
    required String token,
    required int memberId,
    required int clusterId,
    required double quantityLiters,
    required DateTime productionDate,
  }) async {
    await apiClient.post(
      '/milk-production-logs',
      token: token,
      headers: {
        'Idempotency-Key': _idempotencyKeyProvider.create('milk-log'),
      },
      body: {
        'member_id': memberId,
        'cluster_id': clusterId,
        'quantity_liters': quantityLiters,
        'production_date': productionDate.toIso8601String().split('T').first,
        'source': 'mobile',
      },
    );
  }
}
