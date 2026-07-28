import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/material.dart';

enum ConnectivityStatus { online, offline }

class NetworkProviderController with ChangeNotifier {
  ConnectivityStatus _status = ConnectivityStatus.online;

  ConnectivityStatus get status => _status;

  NetworkProviderController() {
    _initConnectivityListener();
    _checkConnectivityOnInit();
  }

  void _initConnectivityListener() {
    Connectivity().onConnectivityChanged.listen((List<ConnectivityResult> results) {
      final connectivityResult = results.first;

      if (connectivityResult == ConnectivityResult.none) {
        _updateStatus(ConnectivityStatus.offline);
      } else {
        _updateStatus(ConnectivityStatus.online);
      }
    });
  }

  void _checkConnectivityOnInit() async {
    final results = await Connectivity().checkConnectivity();
    final connectivityResult = results.isNotEmpty ? results.first : ConnectivityResult.none;
    if (connectivityResult == ConnectivityResult.none) {
      _updateStatus(ConnectivityStatus.offline);
    } else {
      _updateStatus(ConnectivityStatus.online);
    }
  }

  void _updateStatus(ConnectivityStatus newStatus) {
    if (_status != newStatus) {
      _status = newStatus;
      notifyListeners();
    }
  }
}
