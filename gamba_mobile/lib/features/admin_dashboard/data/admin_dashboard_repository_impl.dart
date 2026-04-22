import '../../../core/network/api_client.dart';

class AdminDashboardSummary {
  const AdminDashboardSummary({
    required this.activeMembers,
    required this.todayMilkVolumeLiters,
    required this.pendingApprovals,
    required this.unresolvedConflicts,
  });

  final int activeMembers;
  final double todayMilkVolumeLiters;
  final int pendingApprovals;
  final int unresolvedConflicts;

  factory AdminDashboardSummary.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;
    final kpis = data['kpis'] as Map<String, dynamic>;
    final approvals = data['approvals'] as List<dynamic>? ?? const [];
    final exceptions = data['exceptions'] as List<dynamic>? ?? const [];

    int exceptionCount(String code) {
      final match = exceptions.cast<Map<String, dynamic>>().firstWhere(
            (item) => item['code'] == code,
            orElse: () => <String, dynamic>{},
          );

      return (match['count'] as num?)?.toInt() ?? 0;
    }

    return AdminDashboardSummary(
      activeMembers: kpis['active_members'] as int? ?? 0,
      todayMilkVolumeLiters: (kpis['today_milk_volume_liters'] as num?)?.toDouble() ?? 0,
      pendingApprovals: approvals.isEmpty
          ? 0
          : ((approvals.first as Map<String, dynamic>)['count'] as num?)?.toInt() ?? 0,
      unresolvedConflicts: exceptionCount('unresolved_sync_conflicts'),
    );
  }
}

class AdminDashboardRepositoryImpl {
  AdminDashboardRepositoryImpl({
    required this.apiClient,
  });

  final ApiClient apiClient;

  Future<AdminDashboardSummary> fetch({
    required String token,
    required Map<String, String> privilegedHeaders,
  }) async {
    final response = await apiClient.get(
      '/admin/dashboard-summary',
      token: token,
      headers: privilegedHeaders,
    );

    return AdminDashboardSummary.fromJson(response);
  }
}
