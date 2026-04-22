import '../../../core/network/api_client.dart';
import '../domain/entities/member_home_summary.dart';

class MemberHomeRepositoryImpl {
  MemberHomeRepositoryImpl({
    required this.apiClient,
  });

  final ApiClient apiClient;

  Future<MemberHomeSummary> fetch(String token) async {
    final response = await apiClient.get('/member-home', token: token);

    return MemberHomeSummary.fromJson(response);
  }
}
