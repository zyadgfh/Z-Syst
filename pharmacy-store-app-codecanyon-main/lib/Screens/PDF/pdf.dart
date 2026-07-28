import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_pdfview/flutter_pdfview.dart';
import 'package:path_provider/path_provider.dart';
import 'package:permission_handler/permission_handler.dart';

class PDFViewerPage extends StatefulWidget {
  final String path;

  const PDFViewerPage({super.key, required this.path});

  @override
  PDFViewerPageState createState() => PDFViewerPageState();
}

class PDFViewerPageState extends State<PDFViewerPage> {
  Future<void> _downloadPDF() async {
    final filePath = widget.path;

    if (Platform.isAndroid) {
      var status = await Permission.storage.status;
      if (status != PermissionStatus.granted) {
        status = await Permission.storage.request();
      }
      final downloadsFolderPath = '/storage/emulated/0/Download/';
      final fileName = 'PosPro-1.pdf';
      final dir = Directory(downloadsFolderPath);
      final file = File('${dir.path}/$fileName');

      final byteData = await File(filePath).readAsBytes();
      await file.writeAsBytes(byteData);

      EasyLoading.showSuccess('PDF Downloaded');
      print('PDF downloaded to: ${file.path}');
    } else if (Platform.isIOS) {
      final fileName = 'PosPro-1.pdf';
      final dir = await getApplicationDocumentsDirectory();
      final file = File('${dir.path}/$fileName');

      final byteData = await File(filePath).readAsBytes();
      await file.writeAsBytes(byteData);

      EasyLoading.showSuccess('PDF Downloaded');
      print('PDF downloaded to: ${file.path}');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Invoice Viewer',
          style: TextStyle(
            color: Colors.black,
            fontSize: 20.0,
          ),
        ),
        iconTheme: const IconThemeData(color: Colors.black),
        centerTitle: true,
        backgroundColor: Colors.white,
        elevation: 0.0,
        actions: [
          IconButton(
            icon: Icon(Icons.download),
            onPressed: _downloadPDF,
          ),
        ],
      ),
      body: PDFView(
        filePath: widget.path,
      ),
    );
  }
}
