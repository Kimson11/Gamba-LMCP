enum UserRole {
  member,
  clusterSupervisor,
  coopAdmin,
  countryAdmin,
  systemAdmin,
  financeOfficer,
  treasurer,
  marketplaceManager,
  processor,
  auditor,
  unknown;

  bool get isAdminLike {
    return switch (this) {
      UserRole.coopAdmin ||
      UserRole.countryAdmin ||
      UserRole.systemAdmin ||
      UserRole.clusterSupervisor ||
      UserRole.financeOfficer ||
      UserRole.treasurer ||
      UserRole.marketplaceManager => true,
      _ => false,
    };
  }

  static UserRole fromValue(String? value) {
    return switch (value) {
      'member' => UserRole.member,
      'cluster_supervisor' => UserRole.clusterSupervisor,
      'coop_admin' => UserRole.coopAdmin,
      'country_admin' => UserRole.countryAdmin,
      'system_admin' => UserRole.systemAdmin,
      'finance_officer' => UserRole.financeOfficer,
      'treasurer' => UserRole.treasurer,
      'marketplace_manager' => UserRole.marketplaceManager,
      'processor' => UserRole.processor,
      'auditor' => UserRole.auditor,
      _ => UserRole.unknown,
    };
  }
}

class ScopeSummary {
  const ScopeSummary({
    required this.cooperativeIds,
    required this.clusterIds,
    required this.memberId,
  });

  final List<int> cooperativeIds;
  final List<int> clusterIds;
  final int? memberId;

  factory ScopeSummary.fromJson(Map<String, dynamic> json) {
    return ScopeSummary(
      cooperativeIds: ((json['cooperative_ids'] as List<dynamic>?) ?? const [])
          .map((value) => value as int)
          .toList(),
      clusterIds: ((json['cluster_ids'] as List<dynamic>?) ?? const [])
          .map((value) => value as int)
          .toList(),
      memberId: json['member_id'] as int?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'cooperative_ids': cooperativeIds,
      'cluster_ids': clusterIds,
      'member_id': memberId,
    };
  }
}

class LinkedMember {
  const LinkedMember({
    required this.id,
    required this.cooperativeId,
    required this.clusterId,
    required this.memberNumber,
    required this.status,
  });

  final int id;
  final int cooperativeId;
  final int? clusterId;
  final String memberNumber;
  final String status;

  factory LinkedMember.fromJson(Map<String, dynamic> json) {
    return LinkedMember(
      id: json['id'] as int,
      cooperativeId: json['cooperative_id'] as int,
      clusterId: json['cluster_id'] as int?,
      memberNumber: json['member_number'] as String,
      status: json['status'] as String,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'cooperative_id': cooperativeId,
      'cluster_id': clusterId,
      'member_number': memberNumber,
      'status': status,
    };
  }
}

class SessionSecurity {
  const SessionSecurity({
    required this.mfaEnabled,
    required this.privilegedSessionRequired,
    required this.trustedDeviceCount,
  });

  final bool mfaEnabled;
  final bool privilegedSessionRequired;
  final int trustedDeviceCount;

  factory SessionSecurity.fromJson(Map<String, dynamic> json) {
    return SessionSecurity(
      mfaEnabled: json['mfa_enabled'] as bool? ?? false,
      privilegedSessionRequired: json['privileged_session_required'] as bool? ?? false,
      trustedDeviceCount: json['trusted_device_count'] as int? ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'mfa_enabled': mfaEnabled,
      'privileged_session_required': privilegedSessionRequired,
      'trusted_device_count': trustedDeviceCount,
    };
  }
}

class AuthUser {
  const AuthUser({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
  });

  final int id;
  final String name;
  final String email;
  final UserRole role;

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    return AuthUser(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      role: UserRole.fromValue(json['role'] as String?),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'role': role.name,
    };
  }
}

class AuthSession {
  const AuthSession({
    required this.token,
    required this.user,
    required this.scopes,
    required this.linkedMember,
    required this.security,
  });

  final String token;
  final AuthUser user;
  final ScopeSummary scopes;
  final LinkedMember? linkedMember;
  final SessionSecurity security;

  factory AuthSession.fromLoginResponse(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;

    return AuthSession(
      token: data['token'] as String,
      user: AuthUser.fromJson(data['user'] as Map<String, dynamic>),
      scopes: ScopeSummary.fromJson(data['scopes'] as Map<String, dynamic>),
      linkedMember: data['linked_member'] == null
          ? null
          : LinkedMember.fromJson(data['linked_member'] as Map<String, dynamic>),
      security: SessionSecurity.fromJson(data['security'] as Map<String, dynamic>),
    );
  }

  factory AuthSession.fromCurrentUserResponse(Map<String, dynamic> json, String token) {
    final data = json['data'] as Map<String, dynamic>;

    return AuthSession(
      token: token,
      user: AuthUser.fromJson(data['user'] as Map<String, dynamic>),
      scopes: ScopeSummary.fromJson(data['scopes'] as Map<String, dynamic>),
      linkedMember: data['linked_member'] == null
          ? null
          : LinkedMember.fromJson(data['linked_member'] as Map<String, dynamic>),
      security: SessionSecurity.fromJson(data['security'] as Map<String, dynamic>),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'token': token,
      'user': user.toJson(),
      'scopes': scopes.toJson(),
      'linked_member': linkedMember?.toJson(),
      'security': security.toJson(),
    };
  }

  factory AuthSession.fromStorage(Map<String, dynamic> json) {
    return AuthSession(
      token: json['token'] as String,
      user: AuthUser.fromJson(json['user'] as Map<String, dynamic>),
      scopes: ScopeSummary.fromJson(json['scopes'] as Map<String, dynamic>),
      linkedMember: json['linked_member'] == null
          ? null
          : LinkedMember.fromJson(json['linked_member'] as Map<String, dynamic>),
      security: SessionSecurity.fromJson(json['security'] as Map<String, dynamic>),
    );
  }
}
